<?php
declare(strict_types=1);

namespace Controllers;

use Core\Pages;
use Core\Session;
use Models\UserModel;

/**
 * Autenticación: comprueba credenciales y abre/cierra la sesión.
 * NO es el controlador de UserModel (para eso está UserController).
 */
final class AuthController
{
    // Etapa 5: leed los flash 'error' e 'info' con Session::getFlash()
    // y pasadlos a la vista (la vista no accede a Session).
    public function showLogin(): void
    {
        (new Pages())->render('auth/login', ['error' => null, 'info' => null]);
    }

    public function login(): void
    {
        // TODO (Etapa 2): leer 'email' y 'password' de $_POST
        // 1. Leer email y password de $_POST utilizando operadores de fusión por seguridad
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // 2. Buscar el usuario con findByEmail()
        $user = (new UserModel())->findByEmail($email);

        // 3. Comprobar que el usuario existe y que la contraseña coincide
        if ($user && password_verify($password, $user['password'])) {
            // 4. Regenerar la sesión por seguridad antes de autenticar
            Session::regenerar();

            // 5. Guardar el id del usuario en la sesión y redirigir al perfil
            Session::set('user_id', $user['id']);
            redirect('perfil');
        } else {
            // Si falla (correo no existe o contraseña incorrecta), redirigir al login
            redirect('login');
        }
        // TODO: buscar el usuario con (new UserModel())->findByEmail()
        // TODO: comprobar la contraseña con password_verify()
        // TODO: si es correcta -> Session::set('user_id', ...) y redirect('perfil')
        //       (Etapa 4: llamad antes a Session::regenerar())
        // TODO: si falla -> redirect('login')
        //       (mismo comportamiento si el correo no existe que si la contraseña es incorrecta)
    }

    public function logout(): void
    {
        // TODO (Etapa 2): Session::destruir() y redirect('login')
        // Destruir la sesión por completo y redirigir al login
        Session::destruir();
        redirect('login');
    }
}
