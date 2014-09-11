<?php
/* For licensing terms, see /license.txt */
/**
 * This script generates an entry page for visual heatmap reports
 * @package beeznest.heatmap
 */
/**
 * Init
 */
require_once 'config.php';
//conection:
$link = mysqli_connect($servidor, $user, $pass, $database) or die("Error " . mysqli_error($link));
// data base screen
$sc[0]['screen'] = '1366x768';
$sc[1]['screen'] = '1280x800';
$sc[2]['screen'] = '1024x768';
$sc[3]['screen'] = '1600x900';
$sc[4]['screen'] = '1680x1050';

// function helpers
/**
* unserializar data current array
*/
function formatData($data) {
    $newData = array();
    foreach($data as $key => $value) {
        foreach($value as $indice => $valor) {
            if ($indice == 'data_serial') {
                $newData[$key][$indice] = unserialize($valor);
            } else {
                $newData[$key][$indice] = $valor; 
            }
        }
    }
    return $newData;
}

/*
* order array only x and y
*/
function formarDataXY($data) {
    $newData = array();
    foreach($data as $key => $value) {
        foreach($value as $indice => $valor) {
            if ($indice == 'data_serial') {                
                foreach($valor as $a => $b) {
                    $newData[] = array ('x' => $valor[$a]['x'], 'y' => $valor[$a]['y']);
                    continue;
                }                             
            }
        }
    }
    return $newData;
}

/**
 *
 */
if (!empty($_GET['sc'])) {

    // insert
    $screen = mysqli_real_escape_string($_GET['sc']);
    $id_page = intval($_GET['id_page']);
    $dataResult = array();

    // paginador
    $offset = 0;
    $limit = 250;
    $count = mysqli_fetch_row(mysqli_query($link, "SELECT  count(*) FROM heatmap WHERE screen = '$screen' AND page_id = '$id_page' "));
    $total_pages = ($count[0] > 0) ? ceil($count[0]/$limit) : 1;

    for ($page = 1; $page <= $total_pages; $page++) {
        $offset = ($limit * $page) - $limit;
        $queryLimit = "LIMIT $offset,$limit ";
        $query = "SELECT  data_serial FROM heatmap WHERE screen = '$screen' AND page_id = '$id_page' " . $queryLimit;
        $result = mysqli_query($link, $query,MYSQLI_USE_RESULT);    
        if ($result) {        
            while($row = $result->fetch_assoc()) {  
                $dataResult[] = $row;            
            }
        }
    }
    mysqli_close($link);
    // paginador end

    $array = formatData($dataResult);    
    $point = formarDataXY($array);
?>
<!-- html -->
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Heatmap in browser</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="index, follow" />
  <meta name="description" content="Heatmap in browser" />
  <meta name="keywords" content="Heatmap in browser" />
  <style>
body, html, h2 { margin:0; padding:0; height:100%;}
.demo-wrapper { width:100%; height:100%; position:absolute; background:rgba(0,0,0,.1);}
.heatmap {width: 100%;height: 100%;}
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="demo-wrapper">
      <div class="heatmap">        
      </div>
    </div>
  </div>
  <script src="js/heatmap.min.js"></script>  
  <script>    
    window.onload = function() {
        document.body.style.backgroundImage="url('image/resolution/<?php echo $id_page ?>/<?php echo $screen ?>.png')";
        // data
        <?php $string = '';
        foreach ($point as $key => $arreglo) {
            foreach($arreglo as $indice => $value) {
                $string .= "{x:".$arreglo['x'].", y:".$arreglo['y'].", value:50},"; continue;             
            }
        }        
        if($string != '') {
            $string = substr($string, 0, -1);
        }
        ?>

        //var points = [{x:582, y:500,value: 50}, {x:10, y:10,value: 50}];
        var points = [<?php echo $string ?>];
        var data = { max: 96, data: points };        

        //render
        var heatmapInstance = h337.create({
            container: document.querySelector('.heatmap')
        });
        
        heatmapInstance.setData(data);
    };
  </script>
</body>
</html>
<!-- html -->
<?php } else { ?>
    <h1>Available resolutions 'HeadMap'</h1>
    <?php $pages = getPages($link); ?> 
    <?php foreach($pages as  $key => $value) : ?>
        <h3><?php echo $pages[$key]['url'] ?></h3>
        <?php $id_page = $pages[$key]['id']; ?>

        <ul>
        <?php for ($i = 0; $i < count($sc); $i++) : ?> 
            <li><a href="?id_page=<?php echo $id_page ?>&sc=<?php echo $sc[$i]['screen'] ?>"><?php echo $sc[$i]['screen'] ?></a></li>
        <?php endfor;?>
        </ul>
    <?php endforeach;
} 

/**
 * List all pages
 */
function getPages($link)
{
    $query = 'SELECT id, url FROM page';
    $data = array();
    $result = mysqli_query($link, $query);
    if (mysqli_num_rows($result) > 0 ) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    mysqli_close($link);

    return $data;    
}
