<?php

namespace Evergreen\Generic\Console;

use Illuminate\Console\Command;
use League\Flysystem\MountManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Adapter\Local as LocalAdapter;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Log;

class MakeEgcCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:egc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install default EGC stuff';

    protected $ssh = null;
    protected $maxAttempts = 3;
    protected $currentAttempts = 0;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->output("Start MakeEgcCommand");
        $this->output("checking system requirements...");

        //check whether the system has node and npm installed, if not suggest a user installs them.
        $node = exec('node -v', $nodeArray, $exitCode);
        if ($node == '') {
            $this->output("Node isn't installed", "warning");
            $continue = $this->choice("Are you sure you want to continue?", ['no','yes']);
            if ($continue == "no") {
                $this->output("Please install node from here: https://github.com/creationix/nvm", "error");
                die();
            }
        } else {
            $this->output("You have version '".$node."' of NODE installed");
        }

        $npm = exec('npm -v', $npmArray, $exitCode);
        if (intval($npm) <= 0) {
            $this->output("NPM isn't installed", "warning");
            $continue = $this->choice("Are you sure you want to continue?", ['no','yes']);
            if ($continue == "no") {
                $this->output("Please install npm from here: https://github.com/creationix/nvm", "error");
                die();
            }
        } else {
            $this->output("You have version '".$npm."' of NPM installed");
        }

        //We need to determine if this is a brand new Laravel project, or an existing one.
        $fresh = $this->choice("Is this system a fresh Laravel install? Or an existing project?", ['Fresh','Pulled from GIT']);

        //Does the user want to update the Laravel storage permissions?
        $updateStoragePermissions = $this->choice("Do you want to update the storage permissions?", ['no','yes']);
        if ($updateStoragePermissions == "yes") {
            $storagePath = storage_path();
            $mkdir = array();

            //Change the permisisons
            $chmod = $this->ask("Please enter your chmod permissions: ", "775");
            $this->exec("sudo chmod -R " . $chmod . " " . $storagePath);

            //Check all the relevant storage folders exist, the structure should be:
            /*
                /storage
                    /logs
                    /framework
                        /cache
                        /sessions
                        /views
                    /app
                        /public
             */
            if (!file_exists($storagePath)) {
                $mkdir[] = $storagePath;
            }

            if (!file_exists($storagePath."/logs")) {
                $mkdir[] = $storagePath."/logs";
            }

            if (!file_exists($storagePath."/framework")) {
                $mkdir[] = $storagePath."/framework";
            }
            if (!file_exists($storagePath."/framework/cache")) {
                $mkdir[] = $storagePath."/framework/cache";
            }
            if (!file_exists($storagePath."/framework/sessions")) {
                $mkdir[] = $storagePath."/framework/sessions";
            }
            if (!file_exists($storagePath."/framework/views")) {
                $mkdir[] = $storagePath."/framework/views";
            }

            if (!file_exists($storagePath."/app")) {
                $mkdir[] = $storagePath."/app";
            }
            if (!file_exists($storagePath."/app/public")) {
                $mkdir[] = $storagePath."/app/public";
            }

            if (!empty($mkdir)) {
                foreach ($mkdir as $dir) {
                    $this->exec("mkdir ".$dir);
                }
            }

            //If we have create any new folders, chmod them
            if (!empty($mkdir)) {
                $this->exec("sudo chmod -R " . $chmod . " " . $storagePath);
            }
        }

        //Does the user want to update their .env file?
        //APP_NAME=Laravel

        //
        $envChoice = $this->choice("Do you want to update your .env?", ['no','yes']);
        if ($envChoice == "yes") {
            $questions = [
                'EGC_PROJECT_NAME' => '',
                'APP_NAME' => 'Page Title',
                'APP_ENV' => 'local',
                'APP_DEBUG' => true,
                'APP_LOG_LEVEL'=> 'debug',
                'APP_URL'=> '',
                'DB_CONNECTION'=> 'mysql',
                'DB_HOST'=> '127.0.0.1',
                'DB_PORT'=> '3306',
                'DB_DATABASE'=> '',
                'DB_USERNAME'=> '',
                'DB_PASSWORD'=> '',
                'BROADCAST_DRIVER'=> 'log',
                'CACHE_DRIVER'=> 'array',
                'SESSION_DRIVER'=> 'file',
                'MAIL_DRIVER' => 'sendmail',
            ];

            $answers = [];
            foreach ($questions as $q => $answer) {
                if ($q == "DB_USERNAME" && !empty($questions['DB_DATABASE'])) {
                    $answer = $questions['DB_DATABASE'];
                }
                if (empty($answer)) {
                    $answer = $this->ask("What is your: ".$q);
                } else {
                    $answer = $this->ask("What is your: ".$q, $answer);
                }

                if ($q == "APP_NAME") {
                    $answer = "\"$answer\""; // always a string in case it's got spaces.
                }

                $questions[$q] = $answer;
                $answers[] = $q."=".$answer;
            }

            $newEnv = [];
            $env = [];
            $appKeyFound = false;

            if (file_exists(base_path(".env"))) {
                $env = file_get_contents(base_path(".env"));
                $env = explode("\n", $env);
                foreach ($env as $key => $line) {
                    $line = explode("=", $line);
                    if (count($line) == 2) {
                        if ($line[0] == "APP_KEY" && !empty($line[1])) {
                            $appKeyFound = true;
                        }
                        $newEnv[$line[0]] = $line[1];
                    }
                }
            }

            //No app key was found, Laravel cannot run without one, so let's create one and tell the user.
            if ($appKeyFound == false) {
                $this->output("No APP_KEY found, we will generate one for you.");

                $key = 'base64:'.base64_encode(random_bytes(
                    $this->laravel['config']['app.cipher'] == 'AES-128-CBC' ? 16 : 32
                ));

                $newEnv["APP_KEY"] = $key;
            }

            foreach ($questions as $key => $line) {
                $newEnv[$key] = $line;
                $this->output($key.'='.$line);
            }

            //Before we change the .env, let the user decide if the details are correct.
            $go = $this->choice("Are you sure these details are correct?", ['no','yes']);
            if ($go == "yes") {
                if (file_exists(base_path(".env"))) {
                    copy(base_path(".env"), base_path(".env.old-".time()));
                }
                $envString = '';
                foreach ($newEnv as $key => $line) {
                    if ($envString != '') {
                        $envString.="\n";
                    }

                    $envString.=$key.'='.$line;
                }

                file_put_contents(base_path(".env"), $envString);
            } else {
                $this->output("Please try again", "error");
                die();
            }
        }

        //Does the user want to run php artisan vendor:publish?
        $pavp = $this->choice("Run php artisan vendor:publish? --all", ['no','yes']);
        if ($pavp == "yes") {
            $this->exec("php artisan vendor:publish --all");
        }

        //If this is a fresh install, suggest a user overwrites the following 4 files:
        if ($fresh == "Fresh") {
            $this->copyFile(app_path('User.php'), __DIR__.'/../app/User.php');
            $this->copyFile(base_path('package.json'), __DIR__."/../package.json");
            $this->copyFile(base_path('webpack.mix.js'), __DIR__."/../webpack.mix.js");
            $this->copyFile(base_path('resources/assets/js/app.js'), __DIR__."/../assets/js/app.js");
            $this->copyFile(base_path('resources/assets/sass/app.sass'), __DIR__."/../assets/sass/app.sass");
            $this->copyFile(base_path('resources/assets/sass/_variables.sass'), __DIR__."/../assets/sass/_variables.sass");
        }

        //Re-generate composer autoload
        $this->exec("composer dump-autoload");

        //Allow a user to run npm and bower install
        $npmInstall = $this->choice("Do you want to run 'npm install' ?", ['no','yes']);
        if ($npmInstall == "yes") {
            $this->exec("npm install");
            $this->exec("npm run dev");
        }

        //Allow the user to run php artisan migrate, this also runs the seeders.
        if ($fresh == "Fresh") {
            $runMigrate = $this->choice("Do you want to run php artisan migrate?", ['no','yes']);
            if ($runMigrate == "yes") {
               // $command = "cd ".base_path(). " && php artisan migrate; php artisan db:seed --class=UsersTableSeeder; php artisan db:seed --class=RolesTableSeeder; php artisan db:seed --class=PermissionsTableSeeder; php artisan db:seed --class=CountryTableSeeder; php artisan db:seed --class=CountyTableSeeder";
                $command = "cd ".base_path(). " && php artisan migrate; php artisan db:seed --class=EGLUsersTableSeeder; php artisan db:seed --class=EGLRolesTableSeeder";
                $this->runFromExternal($command);
            }

            $editDatabase = $this->choice("Do you want to edit database.php?", ['no','yes'], 1);
            if ($editDatabase == 'yes') {
                if (file_exists(config_path("database.php"))) {
                    $database = file_get_contents(config_path("database.php"));
                    $database = str_replace("utf8mb4", "utf8", $database);
                    file_put_contents(config_path("database.php"), $database);
                } else {
                    $this->output("cannot find database.php", "error");
                }
            }
        }

        //remove default scss
        if (file_exists(base_path('resources/assets/sass/app.scss'))) {
            $removeController = $this->choice("Do you want to remove Laravel default SCSS stylesheet?", ['no','yes'], 1);
            if ($removeController == 'yes') {
                $this->output("Removing Laravel styles");

                unlink(base_path('resources/assets/sass/app.scss'));
                $this->output("Done");
            }
        }

        if (file_exists(base_path('resources/views/welcome.blade.php'))) {
            $removeWelcome = $this->choice("Do you want to remove Laravel default welcome blade?", ['no','yes'], 1);
            if ($removeWelcome == 'yes') {
                $this->output("Removing Laravel welcome blade");

                unlink(base_path('resources/views/welcome.blade.php'));
                $this->output("Done");
            }
        }

        if ($fresh == "Fresh") {
            $file = base_path("routes/web.php");
            if (file_exists($file)) {
                $web = file_get_contents($file);
                if (strpos($web, "return view('welcome');") !== false) {
                    $removeLaravelRoute = $this->choice("Do you want to remove the default Laravel route?", ['no','yes'], 1);
                    if ($removeLaravelRoute == 'yes') {
                        $web = str_replace("return view('welcome');", "return redirect('/user');", $web);
                        file_put_contents($file, $web);
                    }
                }
            }
        }


        $this->output("Finish MakeEgcCommand");
    }

    /**
     * [This function asks a user if they want to overwrite $old with $new, it will create a backup of $old in the format: $old.old-time()]
     * @param  string $old [The old file that will be overwritten]
     * @param  string $new [The new file that will be copied from]
     */
    public function copyFile($old, $new)
    {
        $replace = $this->choice("Do you want to overwrite ".$old." with ".$new, ['no','yes']);
        if ($replace == "yes") {
            if (file_exists($new)) {
                if (file_exists($old)) {
                    $this->output("Creating backup");
                    rename($old, $old.".old-".time());
                }

                $this->line("<info></info>");
                copy($new, $old);
            } else {
                $this->output($new." doesn't exist", "error");
            }
        }
    }

    /**
     * [This function asks a user if they want to move $new to $old, it will create a backup of $old in the format: $old.old-time()]
     * @param  string $old [The old file that will be overwritten]
     * @param  string $new [The new file where it will be moved from]
     */
    public function replaceFile($old, $new)
    {
        $replace = $this->choice("Do you want to replace ".$old." with ".$new, ['no','yes']);
        if ($replace == "yes") {
            if (file_exists($new)) {
                if (file_exists($old)) {
                    $this->output("Creating backup");
                    rename($old, $old.".old-".time());
                } else {
                    $this->output($old." doesn't exist", "error");
                }
                $this->line("<info></info>");
                rename($new, $old);
            } else {
                $this->output($new." doesn't exist", "error");
            }
        }
    }

    /**
     * [This function runs the php exec or shell_exec with the $command]
     * @param  string  $command [The command that will be run]
     * @param  boolean $print   [A boolean representing whether to output the response]
     * @param  boolean $shell   [A boolean representing whether to run shell_exec rather than exec]
     */
    public function exec($command, $print = true, $shell = false)
    {
        $this->line("");
        $this->output("running: ".$command);

        if ($shell) {
            shell_exec($command);
            $result = '';
        } else {
            exec($command, $result);
        }
        if ($print) {
            if (is_array($result)) {
                foreach ($result as $line) {
                    $this->output($line);
                }
            } else {
                $this->output($result);
            }
        }
    }

    /**
     * [A generic function to easily output information]
     * @param  string $message [The message that will be output]
     * @param  string $type    [The type of message to output]
     */
    public function output($message, $type = 'info')
    {
        switch ($type) {
            case "error":
                Log::error($message);
                $this->error($message);
                break;
            case "warning":
                Log::warning($message);
                $this->warn($message);
                break;
            case "info":
            default:
                Log::info($message);
                $this->line("<info>".$message."</info>");
                break;
        }
    }

    public function runFromExternal($command)
    {
        //Allow the user to SSH into an external server of their choice.
        $serverChoice = $this->choice("Does this need to be run from an external server? Such as aamilne?", ['no','yes']);
        if ($serverChoice == "yes") {
            if ($this->ssh == null) {
                while (is_null($this->ssh) && $this->currentAttempts < $this->maxAttempts) {
                    $this->attemptLogin();
                }
            }
            if (is_null($this->ssh)) {
                $tryAgain = $this->choice("You have failed to login 3 times, do you want to try again?", ['no', 'yes']);
                if ($tryAgain == "yes") {
                    $this->currentAttempts = 0;
                    $this->runFromExternal($command);
                } else {
                    $tryAgain = $this->output("The following command failed to run", "error");
                    $tryAgain = $this->output($command);
                }
            } else {
                $result = $this->ssh->exec($command);
                if (is_array($result)) {
                    foreach ($result as $line) {
                        $this->output($line);
                    }
                } else {
                    $this->output($result);
                }
            }
        } else {
            $this->exec($command);
        }
    }

    public function attemptLogin()
    {
        $serverName = $this->ask("What is the server name?", "aamilne");
        $serverUsername = $this->ask("What is the server username?", "root");
        $serverPassword = $this->secret("What is the server password?", "root");
        $ssh = new SSH2($serverName);
        if ($ssh->login($serverUsername, $serverPassword)) {
            $this->ssh = $ssh;
            return true;
        } else {
            $this->output("Incorrect credentials, you have ".($this->maxAttempts - $this->currentAttempts)." attempts remaining", "error");
            $this->currentAttempts++;
            return false;
        }
    }
}
