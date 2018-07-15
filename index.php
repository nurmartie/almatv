<?php

class Akcii
{
    protected $connection;
    function __construct()
    {
        $this->connection = mysqli_connect("127.0.0.1", "root", "", "zadanie_db");
        if(mysqli_connect_errno())
        {
            printf("Соединение провалено: %s\n", mysqli_connect_error()); // выводим ошибку
            exit(); // завершаем скрипт
        }
        mysqli_set_charset($this->connection, "UTF8");
    }

    public function create_table()
    {
        // 1. Создание таблицы
        $sql_check = "SELECT 1 FROM akcii LIMIT 1;"; // запрос для проверки: существует ли таблица уже
        $result = mysqli_query($this->connection, $sql_check);
        if($result)
        {
            echo "1. Такая таблица уже существует!<br><br>";
        }
        else
        {
            $sql = "CREATE TABLE IF NOT EXISTS `akcii` (
                id INT PRIMARY KEY,
                name VARCHAR(255),
                date_start INT,
                date_finish INT,
                status VARCHAR(3),
                UNIQUE KEY(`id`)
            );"; // подготовим запрос
            $result = mysqli_query($this->connection, $sql); // делаем запрос в базу данных
            if(!$result)
            {
                echo mysqli_error($this->connection); // вывод ошибки
            }
            else {
                echo "1. Таблица akcii успешно создана!<br><br>";
            }
        }
    }

    // импортирование csv файла в таблицу
    public function import_file($file_name)
    {
        $error = false;
        $myfile = fopen($file_name, "r") or die("Не могу открыть файл!");
        while(!feof($myfile))
        {
            $row = fgets($myfile);
            if(strpos($row, "ID акции") == false) // пропускаем первую строку
            {
                if($row != "")
                {
                    $arr = explode(";", $row);
                    $id = $arr[0];
                    $name = str_replace(array("\"", "'"), "", $arr[1]);
                    $date_start = strtotime($arr[2]);
                    $date_finish = $arr[3];
                    $status = $arr[4];
                    $sql = "INSERT IGNORE INTO akcii (`id`, `name`, `date_start`, `date_finish`, `status`)
                            VALUES ($id, '$name', '$date_start', '$date_finish', '$status')";
                    if(!mysqli_query($this->connection, $sql))
                    {
                        $error = true;
                    }
                }
            }
        }
        if(!$error)
        {
            echo "2. Файл успешно импортирован!<br><br>";
        }
        else {
            echo "2. Не все данные импортированы!<br><br>";
        }
        fclose($myfile);
    }

    public function change_status()
    {
        $sql = "SELECT * FROM akcii ORDER BY RAND() LIMIT 1";
        $result = mysqli_query($this->connection, $sql);
        $row = mysqli_fetch_assoc($result);
        mysqli_query($this->connection, "UPDATE akcii SET `status` = CASE WHEN `status` = 'Off' THEN 'On' ELSE 'Off' END WHERE `id` = " . $row['id']);
        echo "3. Статус рандомной строки успешно изменен на противоположный!<br>";
        echo implode(";", $row) . "<br><br>";
    }

    public function generate_urls($file_name)
    {
        $myfile = fopen($file_name, "r") or die("Не могу открыть файл!");
        while(!feof($myfile))
        {
            $row = fgets($myfile);
            if(strpos($row, "ID акции") == false) // пропускаем первую строку
            {
                if($row != "")
                {
                    $arr = explode(";", $row);
                    $arr[1] = str_replace(array("\"", "'", "%"), "", $arr[1]);
                    $name = str_replace(array(",", ".", "=", ":", "?", "+", "!", ";", " "), "-", mb_strtolower($arr[1], "UTF-8"));
                    $name = preg_replace('/-+/', '-', $name);
                    $name = $this->translit($name);
                    $url = $arr[0] . "-" . $name;
                    echo $url . "<BR>";
                }
            }
        }
    }

    public function translit($string)
    {
        $string = mb_strtolower($string);
    	$alphabet = array(
    	"а"=>"a", "ый"=>"iy", "ые"=>"ie",
    	"б"=>"b", "в"=>"v", "г"=>"g",
    	"д"=>"d", "е"=>"e", "ё"=>"yo",
    	"ж"=>"zh", "з"=>"z", "и"=>"i",
    	"й"=>"y", "к"=>"k", "л"=>"l",
    	"м"=>"m", "н"=>"n", "о"=>"o",
    	"п"=>"p", "р"=>"r", "с"=>"s",
    	"т"=>"t", "у"=>"u", "ф"=>"f",
    	"х"=>"kh", "ц"=>"ts", "ч"=>"ch",
    	"ш"=>"sh", "щ"=>"shch", "ь"=>"",
    	"ы"=>"y", "ъ"=>"", "э"=>"e",
    	"ю"=>"yu", "я"=>"ya", "йо"=>"yo",
    	"ї"=>"yi", "і"=>"i", "є"=>"ye",
    	"ґ"=>"g"
    	);
    	return strtr($string, $alphabet);
    }
}

$akcii = new Akcii;

$akcii->create_table();
$akcii->import_file("file.csv");
$akcii->change_status();
$akcii->generate_urls("file.csv");

?>
