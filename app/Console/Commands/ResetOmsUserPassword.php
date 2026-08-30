<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetOmsUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oms:reset-user-password
                            {email? : Adresa de email a utilizatorului}
                            {--create : Creeaza un administrator daca utilizatorul nu exista}
                            {--name=Administrator OMS : Numele noului administrator}
                            {--status : Afiseaza starea contului fara a modifica parola}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reseteaza parola unui utilizator OMS printr-un prompt securizat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Adresa de email');
        $user = User::where('email', $email)->first();

        if ($this->option('status')) {
            if ($user === null) {
                $this->error('Contul nu exista.');

                return self::FAILURE;
            }

            $this->info(sprintf(
                'Cont gasit. Rol: %s. Parola configurata: %s.',
                $user->role,
                filled($user->password) ? 'da' : 'nu',
            ));

            return self::SUCCESS;
        }

        if ($user === null && ! $this->option('create')) {
            $this->error('Nu exista un utilizator cu aceasta adresa de email.');

            return self::FAILURE;
        }

        $password = $this->secret('Parola noua (minimum 8 caractere)');
        $confirmation = $this->secret('Repeta parola noua');

        if (! is_string($password) || strlen($password) < 8) {
            $this->error('Parola trebuie sa aiba cel putin 8 caractere.');

            return self::FAILURE;
        }

        if ($password !== $confirmation) {
            $this->error('Parolele nu coincid.');

            return self::FAILURE;
        }

        if ($user === null) {
            User::create([
                'name' => $this->option('name'),
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
            ]);
            $this->info('Administratorul a fost creat. Te poti autentifica acum.');

            return self::SUCCESS;
        }

        $user->forceFill(['password' => Hash::make($password)])->save();
        $this->info('Parola a fost actualizata. Te poti autentifica acum.');

        return self::SUCCESS;
    }
}
