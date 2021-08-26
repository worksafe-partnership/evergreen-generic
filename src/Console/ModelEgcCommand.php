<?php
namespace Evergreen\Generic\Console;

use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use League\Flysystem\MountManager;
use Illuminate\Console\Command;
use Yosymfony\Toml\Toml;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Config;
use File;
use Log;

use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class ModelEgcCommand extends Command
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
    protected $signature = 'model:egc {--file=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install default EGC stuff';

    /**
     * These store information from the XML and are used in the stubs
     * @var [string|array]
     */
    protected $dummies = [
        "DummyIdentifierPath" => '',
        "DummySingular" => '',
        "DummyPlural" => '',
        "DummyIcon" => '',
        "DummyModel" => '',
        "DummyController" => '',
        "DummyRouteType" => '',
        "DummyPermissions" => "true",

        "DummyRules" => [],
        "DummyMessages" => [],
        "DummyClass" => '',
        "DummyRequest" => '',

        "DummyDbTable" => '',
        "DummyWithTrashed" => '',
        "DummyTitleName" => '',
        "DummyDatatableName" => '',
        "DummyFillables" => [],
        "DummySoftDeletes" => '',
        "DummyUseSoftDeletes" => '',
        "DummyMigrationLines" => [],
        "DummyInputs" => [],
        "DummyDatatableColumns" => [],
        "DummyDatatableSelect" => [],
        "DummyPrimaryKey" => '',
    ];

    /**
     * A boolean which is set to true if <permissions>true</permissions> is present in the XML
     * @var boolean
     */
    protected $usingSoftDeletes = false;
    protected $datatablesDefault = true;

    protected $autocompletes = [];

    protected $excludedTypes = [
        "increments",
        "bigIncrements",
        "nullableTimestamps",
        "rememberToken",
        "softDeletes",
        "timestamps",
        "uuid"
    ];

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Composer $composer, Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }


    protected function getOptions()
    {
        return [
            ['file', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
        ];
    }

    public function exists($array, $element, $die = true, $default = null, $message = null)
    {
        if (isset($array[$element])) {
            return $array[$element];
        } else if (!is_null($default)) {
            return $default;
        } else if ($die) {
            if (is_null($message)) {
                dd("Please add ".$element);
            } else {
                dd(__LINE__, $message);
            }
        }

        return false;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->input->hasOption('file') && $this->option('file')) {
            $yaml = $this->option('file');

            $file = null;
            if (file_exists(base_path("yaml/".$yaml))) {
                $file = base_path("yaml/".$yaml);
            } else if (file_exists(base_path("yaml/".$yaml.".yaml"))) {
                $file = base_path("yaml/".$yaml.".yaml");
            } else if (file_exists(base_path("yaml/".$yaml.".yml"))) {
                $file = base_path("yaml/".$yaml.".yml");
            }

            $data = Yaml::parse(file_get_contents($file));

            if (is_null($file)) {
                $this->output("cannot find file in ".base_path("yaml"), "error");
                return false;
            }

            $this->dummies['DummyIdentifierPath'] = $this->exists($data, "identifierPath");
            $this->dummies['DummyModel'] = $this->exists($data, "model");
            $name = ucfirst($this->dummies['DummyModel']);

            $showAreYouSure = false;
            $createAll = false;

            if (isset($data['config']) && is_array($data['config'])) {
                $this->dummies['DummySingular'] = $this->exists($data['config'], 'singular');
                $this->dummies['DummyPlural'] = $this->exists($data['config'], 'plural');
                $this->dummies['DummyIcon'] = $this->exists($data['config'], 'icon', true, 'date_range');
                $this->dummies['DummyController'] = $this->exists($data['config'], 'controller', true, $name."Controller");
                $this->dummies['DummyRouteType'] = $this->exists($data['config'], 'route_type');
                $this->dummies['DummyRequest'] = $name."Request";
                $this->dummies['DummyClass'] = $name;
                $table = $this->exists($data, 'dbTable', false, strtolower($this->dummies['DummyPlural']));

                $this->dummies['DummyDbTable'] = "'".str_replace(" ", "_", $table)."'";
                $this->dummies['DummyMigrationFileName'] = date('Y_m_d_His').'_create_'.strtolower($name).'_table';
                $this->dummies['DummyMigrationClass'] = "Create".ucfirst(str_replace(" ", "", $name))."Table";
                $this->dummies['DummySeederClass'] = ucfirst($name)."Seeder";
                $this->dummies['DummySeederClassName'] = ucfirst($this->exists($data, 'dbTable', true, $this->dummies['DummySingular'])."TableSeeder");

                if (isset($data['fields'])) {
                    $createAll = true;
                    $tableMigrations = [];
                    foreach ($data['fields'] as $field) {
                        if ($this->exists($field, 'name')) {
                            if ($this->exists($field, 'type')) {
                                if (in_array($this->exists($field, 'type'), ["increments","bigIncrements"]) && $field['name'] != "id") {
                                    $this->dummies['DummyPrimaryKey'] = "\n    protected ".'$primaryKey = \''.$field['name'].'\';';
                                }

                                //if there is a type, add it to the migration script

                                //should handle this somehow (and probably others)
                                if ($field['type'] == 'multiCheckbox') {
                                    $migration = '$table->string(\''.$field['name'].'\'';
                                } else {
                                    $migration = '$table->'.$field['type'].'(\''.$field['name'].'\'';
                                }

                                if ($this->exists($field, 'migrationArgument', false)) {
                                    if (is_array($field['migrationArgument'])) {
                                        $arguements = '';
                                        foreach ($field['migrationArgument'] as $col) {
                                            if ($arguements != '') {
                                                $arguements.=', ';
                                            }
                                            $arguements.= $col;
                                        }
                                        $migration.= ', '.$arguements;
                                    } else {
                                        $migration.=', '.$field['migrationArgument'];
                                    }
                                }

                                $migration.=')';

                                //add migration modifiers
                                if ($this->exists($field, 'first', false) && filter_var($field['first'], FILTER_VALIDATE_BOOLEAN)) {
                                    $migration.='->first()';
                                }

                                if ($this->exists($field, 'nullable', false) && filter_var($field['nullable'], FILTER_VALIDATE_BOOLEAN)) {
                                    $migration.='->nullable()';
                                }

                                if ($this->exists($field, 'unsigned', false) && filter_var($field['unsigned'], FILTER_VALIDATE_BOOLEAN)) {
                                    $migration.='->unsigned()';
                                }

                                if ($this->exists($field, 'after', false)) {
                                    $this->output("After is only allowed when altering tables so it hasn't been added for ".$field['name']);
                                    $showAreYouSure = true;
                                }

                                if ($this->exists($field, 'default', false)) {
                                    $migration.='->default(\''.$field['default'].'\')';
                                }

                                if ($this->exists($field, 'comment', false)) {
                                    $migration.='->comment(\''.$field['comment'].'\')';
                                }

                                if ($this->exists($field, 'unique', false) && filter_var($field['unique'], FILTER_VALIDATE_BOOLEAN)) {
                                    $migration.='->unique()';
                                }

                                if ($this->exists($field, 'index', false)) {
                                    $migrationLine = '$table->index(';
                                    if (count($this->exists($field, 'index') > 1)) {
                                        $indexCols = '[';
                                        foreach ($field->index as $indexCol) {
                                            if ($indexCols != '[') {
                                                $indexCols.=',';
                                            }
                                            $indexCols.='\''.$indexCol.'\'';
                                        }
                                        $indexCols.= ']';
                                        $migrationLine.= $indexCols;
                                    } else {
                                        $migrationLine.='\''.$field['name'].'\'';
                                    }

                                    if ($this->exists($field, 'indexName', false)) {
                                        $migrationLine.=',\''.$field->indexName.'\'';
                                    }
                                    $migrationLine.=');';
                                    $tableMigrations[] = $migrationLine;
                                }

                                $migration.=';';

                                if ($this->exists($field, 'foreign', false)) {
                                    if ($this->exists($field['foreign'], 'table') && $this->exists($field['foreign'], 'id', false)) {
                                        $migration.="\n            ".'$table->foreign(\''.$field['name'].'\')->references(\''.$field['foreign']['id'].'\')->on(\''.$field['foreign']['table'].'\')';

                                        if ($this->exists($field['foreign'], 'onDelete')) {
                                            $migration.="->onDelete('".$field['foreign']['onDelete']."')";
                                        }
                                        if ($this->exists($field['foreign'], 'onUpdate')) {
                                            $migration.="->onUpdate('".$field['foreign']['onUpdate']."')";
                                        }

                                        $migration.=";";
                                    } else {
                                        $this->output("The foreign key for ".$field['name']." hasn't been added as either the table, or id elements don't exist. ");
                                        $showAreYouSure = true;
                                    }
                                }

                                if (!empty($migration)) {
                                    $this->dummies['DummyMigrationLines'][] = $migration;
                                }

                                //if the type isn't increments (the unique ID)
                                if (!in_array($field['type'], $this->excludedTypes)) {
                                    //add it the the Model fillables
                                    $this->dummies['DummyFillables'][] = "'".$field['name']."'";

                                    $label = $field['name'];
                                    if ($this->exists($field, 'label', false)) {
                                        $label = $field['label'];
                                    }

                                    if ($this->exists($field, 'formType', false)) {
                                        $type = $field['formType'];
                                    } else {
                                        $type = $field['type'];
                                        switch ($type) {
                                            case "integer":
                                            case "bigInteger":
                                            case "decimal":
                                            case "double":
                                            case "float":
                                            case "mediumInteger":
                                            case "smallInteger":
                                            case "tinyInteger":
                                                $type = "number";
                                                break;

                                            case "string":
                                            case "char":
                                            case "ipAddress":
                                            case "macAddress":
                                            case "mediumText":
                                                $type = "text";
                                                break;

                                            case "boolean":
                                                $type = "checkbox";
                                                break;

                                            case "enum":
                                                $type = "select";
                                                break;

                                            case "json":
                                            case "jsonb":
                                                $type = "textArea";
                                                break;

                                            case "longText":
                                            case "text":
                                                $type="ckEditor";
                                                break;
                                        }
                                    }

                                    $input = [
                                        'type' => $type,
                                        'name' => "'".$field['name']."'",
                                        "'label'" => "'".$label."'",
                                        "'value'" => '$record["'.$field['name'].'"]',
                                        "'type'" => '$pageType'
                                    ];

                                    if ($this->exists($field, 'formAttribute', false)) {
                                        foreach ($field->formAttribute as $attribute) {
                                            if ($this->exists($attribute, 'key')) {
                                                if ($this->exists($attribute, 'value')) {
                                                    if ($type == "autoCompleteAjax" && $attribute['key'] == "url") {
                                                        $name = $this->camelCase($field['name']);
                                                        $this->autocompletes[$name] = [
                                                            "url" => $attribute['value']
                                                        ];
                                                        $attribute['value'] = '"/'.strtolower($this->dummies["DummyTitleName"])."/".$attribute['value'].'"';
                                                    }
                                                    $input["'".$attribute['key']."'"] = $attribute['value'];
                                                } else {
                                                    $this->output("A field attribute for ".$field['name']." (".$attribute['key'].") doesn't have a <value>, so it won't been added to the the blade ");
                                                    $showAreYouSure = true;
                                                }
                                            } else {
                                                $this->output("A field attribute for ".$field['name']." doesn't have a <key>, so it won't been added to the the blade ");
                                                $showAreYouSure = true;
                                            }
                                        }
                                    }

                                    if ($field['type'] == "enum" && $type == "select" && !isset($input["'list'"]) && $this->exists($field, 'migrationArgument', false) && count($field['migrationArgument']) > 1) {
                                        $cols = "[\n                ";
                                        foreach ($field['migrationArgument'] as $col) {
                                            if ($cols != "[\n                ") {
                                                $cols.=",\n                ";
                                            }
                                            $cols.='\''.$col.'\' => \''.$col.'\'';
                                        }
                                        $cols.= "\n            ]";
                                        $input["'list'"] = $cols;
                                    }

                                    if ($type == "select" && !isset($input["'list'"])) {
                                        $this->output("No list was added for the ".$field['type']." field so a default list has been added");
                                        $input["'list'"] = '[ \'\' => "Please change this list in the blade"]';
                                    }

                                    //add it the the DummyInputs which will add it to the blade.
                                    $this->dummies['DummyInputs'][] = $input;
                                }
                            } else {
                                $this->output("The field ".$field['name']." doesn't have a type, so it won't been added to the migraion, or the blade ");
                                $showAreYouSure = true;
                            }

                            if (!isset($label)) {
                                $label = $field['name'];
                                if ($this->exists($field, 'label', false)) {
                                    $label = $field['label'];
                                }
                            }

                            if (($this->datatablesDefault && (!isset($field->datatable) || $field->datatable == "true" || $field->datatable == "1")) ||
                                (!$this->datatablesDefault && ($this->exists($field, 'datatable', false) && ($field->datatable == "true" || $field->datatable == "1")))
                            ) {
                                if ($label == "id" || in_array($field['type'], ["increments","bigIncrements"])) {
                                    $label = "Id";
                                }

                                //add the columns to the dummy for the Controller and Model
                                if ($field['name'] == "id") {
                                    $this->dummies['DummyDatatableColumns']["'".$field['name']."'"] = "['visible' => false, 'searchable' => false, 'label' => '".$label."']";
                                } else {
                                    $this->dummies['DummyDatatableColumns']["'".$field['name']."'"] = "['label' => '".$label."']";
                                }
                                $this->dummies['DummyDatatableSelect'][] = "'".$field['name']."'";
                            }

                            //if <request> is set and there is at least 1 <rule> within the request
                            if ($this->exists($field, 'request', false)) {
                                $ruleString = '';
                                foreach ($field['request'] as $rule) {
                                    if ($this->exists($rule, 'type', false)) {
                                        if ($ruleString != '') {
                                            $ruleString.=",";
                                        }
                                        $ruleString.=$rule['type'];

                                        if ($this->exists($rule, 'message', false)) {
                                            $type = explode(":", $rule['type']);
                                            if (isset($type[0])) {
                                                $this->dummies['DummyMessages']["'".$field['name'].'.'.$type[0]."'"] = "'".$rule['message']."'";
                                            }
                                        }
                                        if (isset($this->dummies['DummyRules']["'".$field['name']."'"])) {
                                            $this->dummies['DummyRules']["'".$field['name']."'"].="|";
                                            $this->dummies['DummyRules']["'".$field['name']."'"].= $rule['type'];
                                        } else {
                                            $this->dummies['DummyRules']["'".$field['name']."'"] = $rule['type'];
                                        }
                                    } else {
                                        $this->output("A rule is missing the <type> and hasn't been added to the request");
                                        $showAreYouSure = true;
                                    }
                                }
                            }
                        } else {
                            $this->output("A field doesn't have a name and hasn't been added");
                            $showAreYouSure = true;
                        }
                    }


                    if ($showAreYouSure) {
                        $continue = $this->choice("Some warnings have been displayed, are you sure you want to continue?", ['no','yes']);
                        if ($continue == "no") {
                            $this->output("Exiting...", "error");
                            die();
                        }
                    }

                    if ($this->exists($data, 'softDeletes', false, true)) {
                        $this->output("Adding softdeletes");
                        $this->dummies["DummyUseSoftDeletes"] = "\nuse Illuminate\Database\Eloquent\SoftDeletes;";
                        $this->dummies["DummySoftDeletes"] = "\n    use SoftDeletes;";
                        $this->dummies['DummyMigrationLines'][] = '$table->softDeletes();';
                        $this->dummies['DummyDatatableSelect'][] = "'deleted_at'";
                        $this->dummies['DummyWithTrashed'] = "->withTrashed(can('permanentlyDelete', \$identifier))";
                        $this->usingSoftDeletes = true;
                    }

                    if ($this->exists($data, 'timestamps', false) && filter_var($data['timestamps'], FILTER_VALIDATE_BOOLEAN)) {
                        $this->output("Adding timestamps");
                        $this->dummies['DummyMigrationLines'][] = '$table->timestamps();';
                    }

                    if ($this->exists($data, 'permissions', false)) {
                        if (is_array($data['permissions'])) {
                            if (isset($data['permissions']['on'])) {
                                $data['permissions']["'on'"] = $data['permissions']['on'];
                                unset($data['permissions']['on']);
                            }
                            if (isset($data['permissions']['extra'])) {
                                foreach ($data['permissions']['extra'] as $e => $perm) {
                                    $data['permissions']["'extra'"][$e] = "'".$perm."'";
                                }
                                unset($data['permissions']['extra']);
                            }
                            $data['permissions'] = $this->arrToStr($data['permissions'], true, "    ", "        ", false);
                        }
                        if ($data['permissions']) {
                            $this->dummies['DummyPermissions'] = "true";
                        }
                    }
                }

                $createConfig = "yes";
                if (Config::get("egc.yaml.importSome") == true) {
                    $createConfig = $this->choice("Do you want to create the config?", ['no','yes']);
                }
                if ($createConfig == "yes") {
                    $this->createConfig();
                }

                if ($createAll) {
                    $createModel = "yes";
                    if (Config::get("egc.yaml.importSome") == true) {
                        $createModel = $this->choice("Do you want to create the model?", ['no','yes']);
                    }
                    if ($createModel == "yes") {
                        $this->createModel();
                    }


                    $createController = "yes";
                    if (Config::get("egc.yaml.importSome") == true) {
                        $createController = $this->choice("Do you want to create the controller?", ['no','yes']);
                    }
                    if ($createController == "yes") {
                        $this->createController();
                    }


                    $createRequest = "yes";
                    if (Config::get("egc.yaml.importSome") == true) {
                        $createRequest = $this->choice("Do you want to create the request?", ['no','yes']);
                    }
                    if ($createRequest == "yes") {
                        $this->createRequest();
                    }


                    $createMigration = "yes";
                    if (Config::get("egc.yaml.importSome") == true) {
                        $createMigration = $this->choice("Do you want to create the migration?", ['no','yes']);
                    }
                    if ($createMigration == "yes") {
                        $this->createMigration();
                    }


                    $createBlades = "yes";
                    if (Config::get("egc.yaml.importSome") == true) {
                        $createBlades = $this->choice("Do you want to create the blades?", ['no','yes']);
                    }
                    if ($createBlades == "yes") {
                        $this->createBlades();
                    }

                    $createSeeder = "yes";
                    if (Config::get("egc.yaml.importSome") == true) {
                        $createSeeder = $this->choice("Do you want to create the seeder?", ['no','yes']);
                    }

                    if ($createSeeder == "yes") {
                        $this->createSeeder();
                    }
                }
            } else {
                dd("Please add the config settings");
            }
        }

        $this->output("Finish MakeEgcCommand");
    }

    public function createConfig()
    {
        switch ($this->dummies["DummyRouteType"]) {
            case "module":
                $stub = __DIR__."/stubs/config-basic.stub";
                break;
            default:
                $stub = __DIR__."/stubs/config.stub";
                break;
        }

        if (file_exists($stub)) {
            $configStub = file_get_contents($stub);
            $configStub = str_replace(
                [
                    "DummySingular",
                    "DummyPlural",
                    "DummyIdentifierPath",
                    "DummyRouteType",
                    "DummyModel",
                    "DummyIcon",
                    "DummyController",
                    "DummyDatatableColumns",
                    "DummyPermissions",
                ],
                [
                    $this->dummies["DummySingular"],
                    $this->dummies["DummyPlural"],
                    $this->dummies["DummyIdentifierPath"],
                    $this->dummies["DummyRouteType"],
                    $this->dummies["DummyModel"],
                    $this->dummies["DummyIcon"],
                    $this->dummies["DummyController"],
                    $this->arrToStr($this->dummies["DummyDatatableColumns"], true, "    ", "        "),
                    $this->dummies["DummyPermissions"],
                ],
                $configStub
            );
            $path = $this->generatePath();
            $path = base_path("config/structure/".$path."/config.php");

            $continue = "yes";

            if (file_exists($path)) {
                $continue = $this->choice("The Config (".$path.") already exists, do you want to overwrite it?", ['no','yes']);
            }

            if ($continue == "yes") {
                if (! $this->files->isDirectory(dirname($path))) {
                    $this->files->makeDirectory(dirname($path), 0777, true, true);
                }
                $this->files->put($path, $configStub);
                $this->output("Successfully created ".$path);
            }
        } else {
            $this->output("Cannot find config stub in: ".$stub, "error");
        }
    }

    public function createModel()
    {
        $stub = __DIR__."/stubs/model.stub";
        if (file_exists($stub)) {
            $modelStub = file_get_contents($stub);
            $modelStub = str_replace(
                [
                    "DummyWithTrashed",
                    "DummyUseSoftDeletes",
                    "DummyClass",
                    "DummySoftDeletes",
                    "DummyDbTable",
                    "DummyPrimaryKey",
                    "DummyFillables",
                    "DummyDatatableSelect",
                ],
                [
                    $this->dummies["DummyWithTrashed"],
                    $this->dummies["DummyUseSoftDeletes"],
                    $this->dummies["DummyClass"],
                    $this->dummies["DummySoftDeletes"],
                    $this->dummies["DummyDbTable"],
                    $this->dummies['DummyPrimaryKey'],
                    $this->arrToStr($this->dummies["DummyFillables"], false, "    ", "        "),
                    $this->arrToStr($this->dummies["DummyDatatableSelect"], false, "            ", "                "),
                ],
                $modelStub
            );

            $path = base_path("app/".$this->dummies["DummyClass"].".php");
            $continue = "yes";

            if (file_exists($path)) {
                $continue = $this->choice("The Model (".$path.") already exists, do you want to overwrite it?", ['no','yes']);
            }

            if ($continue == "yes") {
                $this->files->put($path, $modelStub);
                $this->output("Successfully created ".$path);
            }
        } else {
            $this->output("Cannot find model stub in: ".$stub, "error");
        }
    }

    public function createController()
    {
        $stub = __DIR__."/stubs/controller.stub";
        if (file_exists($stub)) {
            $autoCompleteFunctions = '';
            foreach ($this->autocompletes as $name => $info) {
                $autoCompleteFunctions.="\n\n    public function ".$name.'Search(\Request $request)
    {
        $return[] = [
            "id" => 1,
            "text" => \'Please complete the '.$name."Search function in the ".$this->dummies["DummyController"].'\'
        ];
        return $this->jsonResults($return);
    }';
            }
            $controllerStub = file_get_contents($stub);
            $controllerStub = str_replace(
                [
                    "DummyClass",
                    "DummyRequest",
                    "DummyController",
                    "DummyIdentifierPath",
                    "DummyAutoCompleteFunctions"
                ],
                [
                    $this->dummies["DummyClass"],
                    $this->dummies["DummyRequest"],
                    $this->dummies["DummyController"],
                    $this->dummies["DummyIdentifierPath"],
                    $autoCompleteFunctions
                ],
                $controllerStub
            );

            $path = base_path("app/Http/Controllers/".$this->dummies["DummyController"].".php");
            $continue = "yes";

            if (file_exists($path)) {
                $continue = $this->choice("The Controller (".$path.") already exists, do you want to overwrite it?", ['no','yes']);
            }

            if ($continue == "yes") {
                $this->files->put($path, $controllerStub);
                $this->output("Successfully created ".$path);
            }
        } else {
            $this->output("Cannot find controller stub in: ".$stub, "error");
        }
    }

    public function createRequest()
    {
        if (file_exists(__DIR__."/stubs/request.stub")) {
            $requestStub = file_get_contents(__DIR__."/stubs/request.stub");
            foreach ($this->dummies["DummyRules"] as $key => $rule) {
                $this->dummies["DummyRules"][$key] = "'".$rule."'";
            }
            $requestStub = str_replace(
                [
                    "DummyRequest",
                    "DummyRules",
                    "DummyMessages"
                ],
                [
                    $this->dummies["DummyRequest"],
                    $this->arrToStr($this->dummies["DummyRules"]),
                    $this->arrToStr($this->dummies["DummyMessages"]),
                ],
                $requestStub
            );

            $path = base_path("app/Http/Requests/".$this->dummies["DummyRequest"].".php");
            $continue = "yes";

            if (file_exists($path)) {
                $continue = $this->choice("The Request (".$path.") already exists, do you want to overwrite it?", ['no','yes']);
            }

            if ($continue == "yes") {
                $this->files->put($path, $requestStub);
                $this->output("Successfully created ".$path);
            }
        } else {
            $this->output("Cannot find request stub in: ".__DIR__."/stubs/request.stub", "error");
        }
    }

    public function createMigration()
    {
        if (file_exists(__DIR__."/stubs/migration.stub")) {
            $migrationStub = file_get_contents(__DIR__."/stubs/migration.stub");
            $dummyLines = '';
            foreach ($this->dummies["DummyMigrationLines"] as $line) {
                if ($dummyLines != '') {
                    $dummyLines.="\n            ";
                }
                $dummyLines.= $line;
            }
            $migrationStub = str_replace(
                [
                    "DummyMigrationClass",
                    "DummyDbTable",
                    "DummyColumns"
                ],
                [
                    $this->dummies["DummyMigrationClass"],
                    $this->dummies["DummyDbTable"],
                    $dummyLines,
                ],
                $migrationStub
            );

            $path = base_path("database/migrations/".$this->dummies["DummyMigrationFileName"].".php");
            $continue = "yes";

            $files = $this->getFiles(base_path("database/migrations"));
            foreach ($files as $file) {
                $classFile = preg_replace("/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_/", '', $file);
                $newFile = 'create_'.strtolower($this->dummies['DummyClass']).'_table';
                if ($classFile == $newFile) {
                    $this->files->requireOnce(base_path("database/migrations/".$file.".php"));
                    if (class_exists($this->dummies["DummyMigrationClass"])) {
                        $continue = $this->choice("The Migration (".base_path("database/migrations/".$file.".php").") already exists, do you want to overwrite it?", ['no','yes']);
                        $path = base_path("database/migrations/".$file.".php");
                    }
                }
            }

            if ($continue == "yes") {
                $this->files->put($path, $migrationStub);
                $this->output("Successfully created ".$path);

                $runMigrate = $this->choice("Do you want to run php artisan migrate?", ['no','yes']);
                if ($runMigrate == "yes") {
                    $command = "cd ".base_path(). " && php artisan migrate";

                    //Allow the user to SSH into an external server of their choice.
                    $serverChoice = $this->choice("Does this need to be run from an external server? Such as aamilne?", ['no','yes']);
                    if ($serverChoice == "yes") {
                        $serverName = $this->ask("What is the server name?", "aamilne");
                        $serverUsername = $this->ask("What is the server username?", "root");
                        $serverPassword = $this->secret("What is the server password?", "root");

                        $ssh = new SSH2($serverName);
                        if (!$ssh->login($serverUsername, $serverPassword)) {
                            $this->output($serverName." login failed...", "error");
                            unset($ssh);
                        } else {
                            $result = $ssh->exec($command);
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
            }
        } else {
            $this->output("Cannot find migration stub in: ".__DIR__."/stubs/migration.stub", "error");
        }
    }

    public function createBlades()
    {
        //create initial modules folder
        $path = resource_path('views/modules');
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        //create inner module folder
        $path = resource_path('views/modules/'.$this->generatePath());
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        if (file_exists(__DIR__."/stubs/form.stub")) {
            $formStub = file_get_contents(__DIR__."/stubs/form.stub");
            $inputs = '';

            if (file_exists(__DIR__."/stubs/form.stub") && !empty($this->dummies["DummyInputs"])) {
                foreach ($this->dummies["DummyInputs"] as $input) {
                    $inputType = $input['type'];
                    $inputName = $input['name'];
                    unset($input['type']);
                    unset($input['name']);

                    $inputStub = file_get_contents(__DIR__."/stubs/input.stub");
                    $inputStub = str_replace(
                        [
                            "DummyType",
                            "DummyInputName",
                            "DummyFormValues",
                        ],
                        [
                            $inputType,
                            $inputName,
                            $this->arrToStr($input),
                        ],
                        $inputStub
                    );
                    if ($inputs != '') {
                        $inputs.="        ";
                    }
                    $inputs.=$inputStub."\n\n";
                }
            }

            $formStub = str_replace("DummyInputs", $inputs, $formStub);

            $path = resource_path('views/modules/'.$this->generatePath().'/display.blade.php');
            $continue = "yes";

            if (file_exists($path)) {
                $continue = $this->choice("The Blade (".$path.") already exists, do you want to overwrite it?", ['no','yes']);
            }

            if ($continue == "yes") {
                $this->files->put($path, $formStub);
                $this->output("Successfully created ".$path);
            }
        } else {
            $this->output("Cannot find form stub in: ".__DIR__."/stubs/form.stub", "error");
        }
    }

/*@TODO:
    - do we need to run composer dump-autoload?
    - what needs to go in the seeder?
        - columns?
        - permissions?
*/
    public function createSeeder()
    {
        if (file_exists(__DIR__."/stubs/seeder.stub")) {
            $seederStub = file_get_contents(__DIR__."/stubs/seeder.stub");
            $dummyLines = '';
            // foreach ($this->dummies["DummySeederLines"] as $line) {
            //     if ($dummyLines != '') {
            //         $dummyLines.="\n            ";
            //     }
            //     $dummyLines.= $line;
            // }
            $seederStub = str_replace(
                [
                    "DummySeederClass",
                    "DummyDbTable",
                    "DummyColumns"
                ],
                [
                    $this->dummies["DummySeederClass"],
                    $this->dummies["DummyDbTable"],
                    $dummyLines,
                ],
                $seederStub
            );

            $path = base_path("database/seeds/".$this->dummies["DummySeederClass"].".php");
            $continue = "yes";

            $files = $this->getFiles(base_path("database/seeds"));
            foreach ($files as $file) {
                $classFile = preg_replace("/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_/", '', $file);
                $newFile = 'create_'.strtolower($this->dummies['DummyClass']).'_table';
                if ($classFile == $newFile) {
                    $this->files->requireOnce(base_path("database/seeds/".$file.".php"));
                    if (class_exists($this->dummies["DummySeederClass"])) {
                        $continue = $this->choice("The Seeder (".base_path("database/seeds/".$file.".php").") already exists, do you want to overwrite it?", ['no','yes']);
                        $path = base_path("database/seeds/".$file.".php");
                    }
                }
            }

            if ($continue == "yes") {
                $this->files->put($path, $seederStub);
                $this->output("Successfully created ".$path);

                $runSeeder = $this->choice("Do you want to run the seeder?", ['no','yes']);
                if ($runSeeder == "yes") {
                    $command = "cd ".base_path(). " && php artisan db:seed --class=".$this->dummies["DummySeederClass"];

                    //Allow the user to SSH into an external server of their choice.
                    $serverChoice = $this->choice("Does this need to be run from an external server? Such as aamilne?", ['no','yes']);
                    if ($serverChoice == "yes") {
                        $serverName = $this->ask("What is the server name?", "aamilne");
                        $serverUsername = $this->ask("What is the server username?", "root");
                        $serverPassword = $this->secret("What is the server password?", "root");

                        $ssh = new SSH2($serverName);
                        if (!$ssh->login($serverUsername, $serverPassword)) {
                            $this->output($serverName." login failed...", "error");
                            unset($ssh);
                        } else {
                            $result = $ssh->exec($command);
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
            }
        } else {
            $this->output("Cannot find seeder stub in: ".__DIR__."/stubs/seeder.stub", "error");
        }
    }

    public function arrToStr($array, $showKey = true, $initialTab = "        ", $innerTab = "            ", $showSubKey = true)
    {
        $str = "[\n";
        if (is_array($array)) {
            foreach ($array as $key => $line) {
                if ($str != "[\n") {
                    $str.=",\n";
                }
                $str.= $innerTab;
                if ($showKey) {
                    $str.= $key." => ";
                }

                if (is_array($line)) {
                    $innerArray = "[\n";
                    foreach ($line as $key => $l) {
                        if ($showSubKey) {
                            $innerArray.= $key." => ";
                        }
                        if ($innerArray != "[\n") {
                            $innerArray.=",\n";
                        }
                        $innerArray.= $l;
                    }

                    $line = $innerArray."]";
                }
                $str.= $line;
            }
        }
        $str.="\n".$initialTab."]";
        return $str;
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
            case "info":
            default:
                Log::info($message);
                $this->line("<info>".$message."</info>");
                break;
        }
    }

    public function getFiles($path)
    {
        $files = $this->files->glob($path.'/*_*.php');

        // Once we have the array of files in the directory we will just remove the
        // extension and take the basename of the file which is all we need when
        // finding the migrations that haven't been run against the databases.
        if ($files === false) {
            return [];
        }

        $files = array_map(function ($file) {
            return str_replace('.php', '', basename($file));
        }, $files);

        // Once we have all of the formatted file names we will sort them and since
        // they all start with a timestamp this should give us the migrations in
        // the order they were actually created by the application developers.
        sort($files);

        return $files;
    }

    public function camelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = lcfirst($str);

        return $str;
    }

    public function generatePath($last = false, $glue = "/")
    {
        $path = explode(".", $this->dummies['DummyIdentifierPath']);
        if (is_array($path)) {
            if ($last) {
                return end($last);
            }

            return implode($glue, $path);
        }

        return '';
    }
}
