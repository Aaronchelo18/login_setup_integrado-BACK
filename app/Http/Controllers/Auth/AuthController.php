<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Person\Person;
use App\Models\PersonVirtual\PersonVirtual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $correo = $googleUser->getEmail();

            if (!str_ends_with($correo, '@upeu.edu.pe')) {
                return redirect('http://localhost:4200/login?error=dominio');
            }

            $partes = preg_split('/\s+/', trim($googleUser->getName() ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $cantidad = count($partes);
            $nombre = $paterno = $materno = '';

            if ($cantidad === 2) {
                [$nombre, $paterno] = $partes;
            } elseif ($cantidad === 3) {
                [$nombre, $paterno, $materno] = $partes;
            } elseif ($cantidad === 4) {
                $nombre  = $partes[0] . ' ' . $partes[1];
                $paterno = $partes[2];
                $materno = $partes[3];
            } else {
                $nombre  = $partes[0] ?? 'Nombre';
                $paterno = $partes[1] ?? 'Paterno';
                $materno = implode(' ', array_slice($partes, 2)) ?: 'Materno';
            }

            $nombreCompleto = trim("$nombre $paterno $materno");
            $personaVirtual = PersonVirtual::where('correo', $correo)->first();

            if ($personaVirtual && $personaVirtual->verificado) {
                DB::table('efeso.usuario')->updateOrInsert(
                    ['id_persona' => $personaVirtual->id_persona],
                    ['correo'     => $correo]
                );
                $persona = Person::find($personaVirtual->id_persona);
                return $this->redirectWithAuth($persona->id_persona, $correo, $persona);
            }

            DB::beginTransaction();
            $confirmationUrl = URL::temporarySignedRoute(
                'confirm.email',
                now()->addMinutes(60),
                ['correo' => $correo]
            );

            if ($personaVirtual) {
                $this->enviarCorreoVerificacion($correo, $nombreCompleto, $confirmationUrl);
                DB::commit();
                return redirect('http://localhost:4200/login/confirm?correo=' . urlencode($correo));
            }

            $persona = Person::firstOrCreate([
                'nombre'  => $nombre,
                'paterno' => $paterno,
                'materno' => $materno
            ]);

            PersonVirtual::create([
                'id_persona'    => $persona->id_persona,
                'correo'        => $correo,
                'verificado'    => false,
                'estado'        => 0,
                'es_principal'  => false,
                'creado_en'     => now(),
                'ultima_sesion' => now()
            ]);

            $this->enviarCorreoVerificacion($correo, $nombreCompleto, $confirmationUrl);
            DB::commit();
            return redirect('http://localhost:4200/login/confirm?correo=' . urlencode($correo));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Google login error: ' . $e->getMessage());
            return redirect('http://localhost:4200/login?error=auth');
        }
    }

    public function confirmEmail(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response('Enlace inválido o expirado', 403);
        }
        $correo = $request->query('correo');
        $pv = PersonVirtual::where('correo', $correo)->first();
        if (!$pv) return response('Correo no registrado', 404);

        $esPrincipal = !PersonVirtual::where('id_persona', $pv->id_persona)
            ->where('verificado', true)
            ->where('es_principal', true)
            ->where('correo', '!=', $correo)
            ->exists();

        $pv->update(['verificado' => true, 'estado' => 1, 'es_principal' => $esPrincipal]);

        DB::table('efeso.usuario')->updateOrInsert(
            ['id_persona' => $pv->id_persona],
            ['correo'     => $correo]
        );

        $persona = Person::find($pv->id_persona);
        return $this->redirectWithAuth($persona->id_persona, $correo, $persona);
    }

    public function loginWithPassword(Request $request)
    {
        $request->validate(['correo' => 'required|string', 'password' => 'required']);

        $pv = DB::table('global_config.persona_virtual')
            ->whereRaw("split_part(correo, '@', 1) = ?", [$request->correo])
            ->where('estado', 1)
            ->where('es_principal', 1)
            ->first();

        if (!$pv) return response()->json(['success' => false, 'message' => 'Usuario no válido'], 403);

        $idPersona = $pv->id_persona;
        $codigo = DB::table('global_config.persona_alumno')->where('id_alumno', $idPersona)->value('codigo');

        if (!$codigo || $request->password !== $codigo) {
            return response()->json(['success' => false, 'message' => 'Credenciales inválidas'], 401);
        }

        // --- CORRECCIÓN: Obtener TODOS los IDs de Rol ---
        $rolesIds = DB::table('efeso.usuario_rol')
            ->where('id_persona', $idPersona)
            ->pluck('id_rol')
            ->toArray();

        DB::table('efeso.usuario')->updateOrInsert(['id_persona' => $idPersona], ['correo' => $pv->correo]);
        $persona = Person::find($idPersona);
        
        $tokens = $this->generateTokens($idPersona, $pv->correo, $persona, $rolesIds);

        return response()->json([
            'success'      => true,
            'access_token' => $tokens['access_token'],
            'authz_token'  => $tokens['authz_token'],
            'roles_ids'    => $rolesIds
        ]);
    }

    private function generateTokens(int $idPersona, string $correo, $persona = null, array $rolesIds = []): array
    {
        $ahora = time();
        $accessTtl = 60 * 60;
        if (!$persona instanceof Person) $persona = Person::find($idPersona);

        $codigo = DB::table('global_config.persona_alumno')->where('id_alumno', $idPersona)->value('codigo');
        $rolesNombres = $this->getRolesDePersona($idPersona); 

        $iss = env('JWT_ISS', 'code5-api');
        $aud = env('JWT_AUD', 'code5-front');

        $accessPayload = [
            'iss'    => $iss,
            'aud'    => $aud,
            'sub'    => $idPersona,
            'correo' => $correo,
            'roles_ids' => $rolesIds, // <--- Enviamos el ARRAY de IDs (1 y 3)
            'person' => [
                'nombre'  => $persona->nombre,
                'paterno' => $persona->paterno,
                'materno' => $persona->materno,
            ],
            'codigo' => $codigo ?: null,
            'iat'    => $ahora,
            'exp'    => $ahora + $accessTtl,
        ];

        $accessToken = JWT::encode($accessPayload, env('JWT_ACCESS_SECRET', 'auth_secret_key'), 'HS256');

        $authzPayload = [
            'iss'   => $iss,
            'aud'   => $aud,
            'sub'   => $idPersona,
            'roles' => $rolesNombres,
            'roles_ids' => $rolesIds,
            'iat'   => $ahora,
            'exp'   => $ahora + $accessTtl,
        ];

        $authzToken = JWT::encode($authzPayload, env('JWT_AUTHZ_SECRET', 'authz_secret_key'), 'HS256');

        return [
            'access_token' => $accessToken,
            'authz_token'  => $authzToken,
            'expires_in'   => $accessTtl,
            'token_type'   => 'Bearer',
        ];
    }

private function redirectWithAuth(int $idPersona, string $correo, $persona = null)
{
    $rolesIds = DB::table('efeso.usuario_rol')->where('id_persona', $idPersona)->pluck('id_rol')->toArray();
    $tokens = $this->generateTokens($idPersona, $correo, $persona, $rolesIds);

    // Envíalo al puerto 4200 (o el que uses para Setup)
    $data = base64_encode(json_encode([
        'access_token' => $tokens['access_token'],
        'authz_token'  => $tokens['authz_token'],
        'expires_in'   => $tokens['expires_in'],
        'token_type'   => 'Bearer',
    ]));

    return redirect("http://localhost:4200/login?auth={$data}");
}

    private function enviarCorreoVerificacion(string $correo, string $nombreCompleto, string $confirmationUrl): void
    {
        try {
            Mail::send('email.email', [
                'appName' => 'Code5 System',
                'nombre'  => $nombreCompleto,
                'link'    => $confirmationUrl
            ], function ($message) use ($correo) {
                $message->to($correo)->subject('Confirma tu cuenta - Code5');
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando correo: ' . $e->getMessage());
        }
    }

    private function getRolesDePersona(int $idPersona): array
    {
        return DB::table('efeso.usuario_rol as ur')
            ->join('efeso.rol as r', 'r.id_rol', '=', 'ur.id_rol')
            ->where('ur.id_persona', $idPersona)
            ->where('r.estado', 1)
            ->pluck('r.nombre')
            ->map(fn($n) => strtoupper($n))
            ->unique()->values()->toArray();
    }

public function me(Request $request) {
    try {
        // Extraer y decodificar el token JWT
        $token = str_replace('Bearer ', '', $request->header('Authorization'));
        $decoded = JWT::decode($token, new Key(env('JWT_ACCESS_SECRET', 'auth_secret_key'), 'HS256'));
        $idPersona = $decoded->sub;

        // Obtener datos básicos de la persona
        $persona = Person::find($idPersona);
        
        // Obtener el código de alumno (si existe)
        $codigo = DB::table('global_config.persona_alumno')
            ->where('id_alumno', $idPersona)
            ->value('codigo');
        
        // Obtener la lista completa de IDs de roles asociados a la persona desde la tabla intermedia
        $rolesIds = DB::table('efeso.usuario_rol')
            ->where('id_persona', $idPersona)
            ->pluck('id_rol')
            ->toArray();

        return response()->json([
            'success' => true,
            'user' => [
                'id_persona' => $idPersona,
                'codigo'     => $codigo ?? 'S/C',
                'nombres'    => $persona->nombre,
                'apellidos'  => trim($persona->paterno . ' ' . $persona->materno),
                'roles_ids'  => $rolesIds,
                // CORRECCIÓN: Ahora id_rol devuelve el arreglo completo con todos los IDs
                // para que puedas identificar cada rol que posee el usuario.
                'id_rol'     => $rolesIds 
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error'   => 'Token inválido o sesión expirada'
        ], 401);
    }
}
}