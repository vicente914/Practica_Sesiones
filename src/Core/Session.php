<?php
declare(strict_types=1);

namespace Core;

final class Session
{
    // Tiempo máximo que puede pasar sin que el usuario haga ninguna petición
    // Valor para pruebas (segundos). En producción sería mucho mayor.
    private const INACTIVIDAD_MAX = 60;

    // ---------- Etapa 1 y 4: arranque ----------
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Directivas de seguridad recomendadas
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        // Detectar HTTPS dinámicamente
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        // Configurar los parámetros de la cookie de sesión de forma segura
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    // ---------- Etapa 1: métodos básicos ----------
    public static function get(string $clave, mixed $defecto = null): mixed
    {
        return $_SESSION[$clave] ?? $defecto;
    }

    public static function set(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function has(string $clave): bool
    {
        return isset($_SESSION[$clave]);
    }

    public static function remove(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    // ---------- Etapa 1 y 2: cierre de sesión ----------
    public static function destruir(): void
    {
         // Etapa 1: solo session_destroy().
        //Vaciamos todas las variables de la sesion en el script actual
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }



        // Etapa 2 (TODO): completar con los 3 pasos del apartado 3.6
        session_destroy();
    }

    // ---------- Etapa 4: regeneración del ID ----------
    public static function regenerar(): void
    {
        // TODO: session_regenerate_id(true)
        session_regenerate_id(true);
    }

    // ---------- Etapa 5: mensajes flash ----------
    public static function setFlash(string $clave, string $mensaje): void
    {
        // TODO: guardarlo dentro de $_SESSION['_flash'][$clave]
        $_SESSION['_flash'][$clave] = $mensaje;
    }

    public static function getFlash(string $clave): ?string
    {
        // TODO: devolver el mensaje Y borrarlo en el mismo instante
        $mensaje = $_SESSION['_flash'][$clave];
        unset($_SESSION['_flash'][$clave]); //Se borra al instante
        return $mensaje;
    }

    // ---------- Etapa 6: caducidad por inactividad ----------
    public static function comprobarCaducidad(): void
    {
        // Solo si existe 'user_id' en la sesión
        if (self::has('user_id')) {
            $ultima = self::get('ultima_actividad', time());
            
            // Si han pasado más de INACTIVIDAD_MAX segundos desde la última petición
            if ((time() - $ultima) > self::INACTIVIDAD_MAX) {
                // 1) Destruir la sesión actual
                self::destruir();
                
                // 2 & 3) Como destruir() borra la sesión, reabrimos brevemente una nueva sesión 
                // para poder dejar el mensaje flash de caducidad
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }
                self::setFlash('error', 'Tu sesión ha caducado por inactividad.');
                
                // 4) Redirigir al login
                redirect('login');
            }
            
            // Si no caduca, actualizar 'ultima_actividad' con el tiempo actual
            self::set('ultima_actividad', time());
        }
    }
}
