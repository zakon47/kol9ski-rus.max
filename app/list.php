<?php

//для сортировки массива
function cmp($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

$arFileList = glob(PAGE."*");
$s = strlen(PAGE);
$fullfile = array();                        //массив готовых названий файлов
for($i=0;$i<count($arFileList);$i++){
    $f = substr($arFileList[$i],$s,-4);     //только название файла
    if($f != 'list'){
        if($f=='index'){
            $fullfile[] = ' ';
        }else{
            $fullfile[] = $f;
        }
    }
}
usort($fullfile, "cmp");                    //сортируем массив

?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>List</title>
</head>
<body>
<?if(!empty($fullfile)):?>
    <?foreach ($fullfile as $key=>$val):?>
        <p><a href="/<?=$val?>">/<?=$val?></a></p>
    <?endforeach;?>
<?else:?>
список пуст [нету никаких страниц]
<?endif;?>

</body>
</html>