<?php 
/**
 * Template Name: Cloudinary
 *
 */

require __DIR__.'/cloudinary/Cloudinary.php';
require __DIR__.'/cloudinary/Api.php';
require __DIR__.'/cloudinary/Settings.php';

/**
 *  Gets a list of roots (first level folders) in decoded json format (multi-dimensional array).
 *  @return array[i] = { name: ..., path: ... }
 **/
function getRootFolders() {
    global $api;
    $root_folder = $api->root_folders();
    $roots_array = json_encode($root_folder);
    $array_folders = json_decode($roots_array, true);

    foreach ($array_folders['folders'] as $key => $array_folder) {

        $array_result[$array_folder['name']] = $array_folder;
    }

    return $array_result;
}

/**
 *  Adds child folders to each roots folders and all single images
 *  to the relative folder. Completes tree structure.
 *  @return array[i] = { ... folders: [ { name: ..., path: ..., cover:, ..., images: [...] }] }
 **/
// @IMPROVEMENT: controllare su più livelli di cartelle
function addChildFolders($folders) {
    global $api;
    foreach($folders as $key => $folder){
        $json_subfolders = json_encode($api->subfolders($folder['name']));
        $subfolders = json_decode($json_subfolders, true);

        foreach ($subfolders['folders'] as $key_sub => $subfolder) {
            $folders[$key]['folders'][$subfolder['name']] = addSingleImages($subfolder);
        }
    }

    return $folders;
}

/**
 *  Adds single images array and attaches cover url to that folder
 *  @return array[i] = { ... folders: [ { ..., cover:, ..., images: [ { ... }, { ... } ] } ] }
 **/
function addSingleImages($folder) {

    global $api;
    //foreach($folders as $key => $folder){

        $json_folder_images = json_encode($api->resources(array("type" => "upload", "prefix" => $folder['path'])));
        $folder_images = json_decode($json_folder_images, true);
        // Aggiungo campo cover
        $folder['cover'] = cloudinary_url($folder_images['resources'][0]['public_id'], array("width"=>150, "height"=>150, "crop"=>"fill"));

        foreach ($folder_images['resources'] as $key_img => $folder_image) {
            $folder['images'][$folder_image['public_id']] = $folder_image;
        }
        // Aggiungo array images

   // }
    return $folder;
}

/**
 *  Creates json formatted output with all root folders in cloudinary, width relatives subdirs, covers and images in subdirs.
 *  @return json string
 **/
function generateTreeJson() {

    return json_encode(addChildFolders(getRootFolders()));
}

/**
 *  Updates cloudinary content cache JSON file in current theme directory
 *  @return file ./results.json 
 **/
function updateImagesJson(){

    $fp = fopen(__DIR__ . '/results.json', 'w');
    $data = generateTreeJson();
    fwrite($fp, $data);
    fclose($fp);
}

/**
 *  Gets array
 *  @return file ./results.json 
 **/
function parseJsonFile(){
    // Read JSON file
    $json = file_get_contents(__DIR__ . '/results.json');
    //Decode JSON
    $json_data = json_decode($json,true);

    return $json_data;
}

?>
<html>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <style>
        #overlay {
            position:absolute; 
            width:100%;
            height:100%;
            background-color:rgba(255,255,255,0.8);
            text-align:center;
            z-index:999;
            display:none;
        }
        #overlay span {
            margin:200px auto 0 auto;
        }
    </style>

    <body>
        <div id="overlay">
            <span>Contenuto in overlay.</span><a class="close-overlay">CHIUDI</a>
        </div>
        <div class="content">
            
        </div>
        <?php
        /*
        // Codice inline da spezzettare in più funzioni

        // Get json array
        //$root_folders = parseJsonFile();

        // MAIN LOOP ...
        foreach ($root_folders as $key => $root_folder) { ?>

            <h2><?php echo $root_folder['name']; ?></h2>

            <?php 
            $folders = $root_folder['folders']; ?>
            <ul class="root-folder-content">
                <?php
                if(sizeof($folders)){
                    // SUB-FOLDERS LOOP...
                    // Print all subfolders with title and cover
                    foreach ($folders as $key_sub => $folder) { ?>
                        <li class="folder-item">
                            <img src="<?php echo $folder['cover']; ?>" alt="<?php echo $folder['name']; ?>" />
                            <a class="folder-title ajax-link" href="#!<?php echo $folder['name']; ?>" data-parent="<?php echo $key; ?>" data-folder="<?php echo $key_sub; ?>" >
                                <h3><?php echo $folder['name']; ?></h3>
                            </a>
                            <ul class="folder-content">
                                <?php
                                $images = $folder['images'];
                                if(sizeof($images)){
                                    // IMAGES LOOP...
                                    // Print all images
                                    foreach ($images as $key_i => $image) { ?>
                                        <li class="image-item">
                                            <a href="<?php echo $image['url']; ?>" target="_blank"><?php echo $image['public_id']; ?></a> 
                                            <br/>
                                            <span><?php echo number_format(intval($image['bytes']) * pow (10,-6), 2, '.', ''); ?>Mb</span>
                                        </li>
                                    <?php } // END FOR IMAGES ?>
                                <?php } // END IF IMAGES ?>
                            </ul>
                        </li>
                    <?php } // END FOR FOLDERS ?>
                <?php } // END IF ?>
            </ul>
        <?php } */?>



        <script>

            function readJSON(file) {
                var request = new XMLHttpRequest();
                request.open('GET', file, false);
                request.send(null);
                if (request.status == 200)
                    return request.responseText;
            };
            
            var temp = readJSON('./wp-content/themes/wk_matteoragni_dev/results.json');

            var obj = JSON.parse(temp);
            var a;

            for(var key in obj){
                $('.content').append('<div class="' + key + '"></div>');
                $('.content .' + key).html('<h2>' + obj[key].name + '</h2>');
            }

            

        </script>
    </body>
</html>
