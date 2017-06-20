<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 6/20/17
 * Time: 11:25
 */
define('__APP__', __DIR__);
require __APP__ . '/vendor/autoload.php';

$string_types = ["varchar", 'enum', 'text', 'time'];
$int_types = ["int", 'tinyint'];
$date_types = ["datetime", 'date', 'timestamp'];
$float_types = ["float"];
$double_types = ["double"];

$db_name = \Framework\Config::get("database.mysql_read.db");
$query ="  select * from information_schema.columns where table_schema = ?";
$database = \ThingORM\DAO\DAOFactory::rawSelect($query,[$db_name])->execute();

$tables = [];

foreach ($database as $item) {
    if(array_key_exists($item->TABLE_NAME, $tables)) {
        $tables[$item->TABLE_NAME][$item->COLUMN_NAME]=$item->DATA_TYPE;
    } else {
        $tables[$item->TABLE_NAME] = [];
        $tables[$item->TABLE_NAME][$item->COLUMN_NAME]=$item->DATA_TYPE;
    }
}

generateEntities($tables,$string_types, $int_types,$date_types, $float_types, $double_types);

generateRepositories($tables);

function generateRepositories($tables) {
    $repository_dir = "repositories";
    // remove all files in directory
    foreach (glob($repository_dir."/*.*") as $filename) {
        if (is_file($filename)) {
            unlink($filename);
        }
    }

    foreach ($tables as $table_name => $table) {
        $repository_names = explode("_",$table_name);
        $repository_names[] = "Repository";

        $repository_name = "";
        foreach ($repository_names as $name) {
            $repository_name = $repository_name.ucfirst($name);
        }
        $entity_file = fopen($repository_dir."/".$repository_name.".php", "w");

        $date = date("m/d/Y");
        $time = date("h:i");
        fwrite($entity_file, "<?php\n");
        fwrite($entity_file, "/**\n");
        fwrite($entity_file, " * Created by ThingSolution Generation tool.\n");
        fwrite($entity_file, " * User: michael\n");
        fwrite($entity_file, " * Date: $date\n");
        fwrite($entity_file, " * Time: $time\n");
        fwrite($entity_file, " */\n");

        // define class
        $entity_namespace = \Framework\Config::get("namespace.repository");
        fwrite($entity_file, "\nnamespace $entity_namespace;");
        fwrite($entity_file, "\n");

        fwrite($entity_file, "\nuse ThingORM\Repository\BaseRepository;");
        fwrite($entity_file, "\n");

        fwrite($entity_file, "\n");


        fwrite($entity_file, "class $repository_name extends BaseRepository {\n");

        fwrite($entity_file, "\n");

        $class_names = explode("_",$table_name);
        $class_names[] = "Entity";

        $class_name = "";
        foreach ($class_names as $name) {
            $class_name = $class_name.ucfirst($name);
        }

        // write construct function
        fwrite($entity_file, "\tpublic function __construct() {\n");
        fwrite($entity_file, "\t\tparent::__construct(\"$table_name\", \"$class_name\");\n");
        fwrite($entity_file, "\t}\n");
        fwrite($entity_file, "\n");

        fwrite($entity_file, "\n");

        //close class
        fwrite($entity_file, "}\n");
        fclose($entity_file);
    }
}

function generateEntities($tables, $string_types, $int_types, $date_types, $float_types, $double_types) {
    $entity_dir = "entities";
    // remove all files in directory
    foreach (glob($entity_dir."/*.*") as $filename) {
        if (is_file($filename)) {
            unlink($filename);
        }
    }

    foreach ($tables as $table_name => $table) {
        $class_names = explode("_",$table_name);
        $class_names[] = "Entity";

        $class_name = "";
        foreach ($class_names as $name) {
            $class_name = $class_name.ucfirst($name);
        }
        $entity_file = fopen("entities/".$class_name.".php", "w");

        $date = date("m/d/Y");
        $time = date("h:i");
        fwrite($entity_file, "<?php\n");
        fwrite($entity_file, "/**\n");
        fwrite($entity_file, " * Created by ThingSolution Generation tool.\n");
        fwrite($entity_file, " * User: michael\n");
        fwrite($entity_file, " * Date: $date\n");
        fwrite($entity_file, " * Time: $time\n");
        fwrite($entity_file, " */\n");

        // define class
        $entity_namespace = \Framework\Config::get("namespace.entity");
        fwrite($entity_file, "\nnamespace $entity_namespace;");
        fwrite($entity_file, "\n");

        fwrite($entity_file, "\nuse ThingORM\Entities\BaseEntity;");
        fwrite($entity_file, "\n");

        fwrite($entity_file, "\n");


        fwrite($entity_file, "class $class_name extends BaseEntity {\n");
        foreach ($table as $column => $data_type) {
            if(in_array($data_type, $string_types)) {
                fwrite($entity_file, "\t/** @var string */\n");
            } elseif (in_array($data_type, $int_types)) {
                fwrite($entity_file, "\t/** @var int */\n");
            } elseif (in_array($data_type, $date_types)) {
                fwrite($entity_file, "\t/** @var \DateTime */\n");
            } elseif (in_array($data_type, $double_types)) {
                fwrite($entity_file, "\t/** @var double */\n");
            } elseif (in_array($data_type, $float_types)) {
                fwrite($entity_file, "\t/** @var float */\n");
            }
            fwrite($entity_file, "\tpublic \$$column;\n");
        }

        fwrite($entity_file, "\n");
        // write construct function
        fwrite($entity_file, "\tpublic function __construct(\$table_object) {\n");
        fwrite($entity_file, "\t\tparent::__construct(\$table_object);\n");
        fwrite($entity_file, "\t}\n");
        fwrite($entity_file, "\n");

        // write function map data
        fwrite($entity_file, "\tpublic function mapData(\$table_object) {\n");
        fwrite($entity_file, "\t\tparent::mapData(\$table_object);\n");

        // write convert function for int, float, double, datetime type
        fwrite($entity_file, "\n");
        foreach ($table as $column => $data_type) {
            if (in_array($data_type, $int_types)) {
                fwrite($entity_file, "\t\t\$this->$column = intval(\$this->$column);\n");
            } elseif (in_array($data_type, $double_types)) {
                fwrite($entity_file, "\t\t\$this->$column = doubleval(\$this->$column);\n");
            } elseif (in_array($data_type, $date_types)) {
                fwrite($entity_file, "\t\t\$this->$column = new \DateTime(\$this->$column);\n");
            } elseif (in_array($data_type, $float_types)) {
                fwrite($entity_file, "\t\t\$this->$column = floatval(\$this->$column);\n");
            }
        }
        // finish function
        fwrite($entity_file, "\t}\n");
        fwrite($entity_file, "\n");

        fwrite($entity_file, "}\n");
        fclose($entity_file);
    }
}




