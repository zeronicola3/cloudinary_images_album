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
            $folder['images'][$folder_image['public_id']]['cover'] = cloudinary_url($folder_images['resources'][$key_img]['public_id'], array("width"=>150, "height"=>150, "crop"=>"fill"));
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
    <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes ">
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>

    <style>

        body {
            width: 90%;
            margin: 0 auto;
            position: relative;
            font-family: sans-serif;
        }
        
        .wk-ap {
            margin-top: 50px;
        }

        .wk-ap .root-folder-title {
            background: #f1f1f1;
            padding: 10px 15px;
            text-align: left;
        }

        .wk-ap .root-folder-title h2 {
            margin: 5px 0;
        }

        #wk-overlay {
            position: fixed; 
            width:100%;
            height:100%;
            left: 0;
            top: 0;
            background-color: white;
            text-align:center;
            z-index:999;
            display:none;
            overflow: auto;
        }

        #wk-overlay .close-overlay {
            position: absolute;
            top: 10px;
            right: 15px;
        }

        #wk-overlay .close-overlay svg {
            width: 14px;
            fill: black;
        }
        
        #wk-overlay .image-folder-title {
            background: #f1f1f1;
            padding: 10px 15px;
            margin: 0;
            text-align: left;
            font-size: 14px;
        }


        ul.folder-list, ul.image-list {
            list-style-type: none;
            padding: 0;
            display: flex;
            flex-direction: row;
            justify-content: flex-start;   
            flex-wrap: wrap; 
            align-content: flex-start;
        }

        ul.image-list {
            width: 90%;
            margin: 20px auto;
        }

        li.folder-item {
            width: calc(50% - 30px);
            max-width: 150px;
            padding-left: 15px;
            padding-right: 15px;
            cursor: pointer;
        }

        .folder-item a img {
            width: 100%;
        }

        .folder-item a h5 {
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 400;
        }

        li.image-item {
            width: calc(50% - 30px);
            max-width: 150px;
            padding-left: 15px;
            padding-right: 15px;
            position: relative;
        }

        li.image-item .image-container {
            width: 100%;
            background-color: #f1f1f1;
            height: 0;
            position: relative;
            padding-top: 100%; /* 1:1 Aspect Ratio */
        }

        li.image-item .image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .image-download {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            background-color: transparent;
            transition: background-color 0.5s ease;
        }

         .image-download a {
            opacity: 0;
            width: 80px;
            height: 80px;
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translateX(-50%) translateY(-50%);
            transition: opacity 0.5s ease;
        }

        li.image-item:hover  .image-download {
            background-color: rgba(0,0,0,.6);
            transition: background-color 0.5s ease;
        }  

        li.image-item:hover  .image-download a {
            opacity: 1;
            transition: opacity 0.5s ease;
        }  
        
        .image-download a svg {
                width: 30px;
                margin-top: 15px;
                fill: white;
        }

        .image-download a span {
            position: relative;
            width: 100%;
            left: 0;
            top: 10px;
            text-decoration: none;
            color: white;
            text-align: center;
            display: inline-block;
        }
        

        .image-item a h5 {
            max-width: 100%;
        }

        @media screen and (min-width: 768px) {
            #wk-overlay {
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
            }

            #wk-overlay .close-overlay {
                position: fixed;
                top: 20px;
                right: 20px;
            }
            
            #wk-overlay .close-overlay svg {
                width: 20px;
                fill: white;
            }   

            #wk-overlay .image-folder-title {
                width: 70%;
                margin: 50px auto;
                background: #f1f1f1;
                padding: 10px 15px;
                text-align: left;
                margin: 100px auto 0;
                font-size: 24px;
            }

            ul.image-list {
                width: 70%;
                margin: 0px auto;
                padding: 50px 15px;
                background-color: white;
                /* display: inline-block; */
            }
        }

        @media screen and (min-width: 1000px) {

        }
        
        
    </style>
    </head>
    <body>
        <div id="wk-overlay">
            <a class="close-overlay">
                <svg x="0px" y="0px"
                        viewBox="35 16 130 129" enable-background="new 35 16 130 129" xml:space="preserve">
                    <g>
                        <polygon points="153.1,20 99.5,73.8 45.8,20 39,26.9 92.6,80.5 39,134.2 45.8,141 99.5,87.4 153.1,141 160,134.2 106.2,80.5 
                            160,26.9 	"/>
                    </g>
                </svg>
            </a>
            <h2 class="image-folder-title"></h2>
            <ul class="image-list"></ul>
        </div>
        <div class="wk-ap">
            
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
            
            /** 
             *  Gets json file content and parse it in JSON
             *  @param  file (json file 'results.json') 
             */
            function readJSON(file) {
                var request = new XMLHttpRequest();
                request.open('GET', file, false);
                request.send(null);
                if (request.status == 200)
                    return JSON.parse(request.responseText);
            };

            var obj = readJSON('./wp-content/themes/wk_matteoragni_dev/results.json');

            getRootFolders(obj);
            
            /** 
             *  Gets root folders and call getChildFolders() for single folders content
             *  @param  obj (json parser object) 
             */
            function getRootFolders(obj){
                // loops each root folders
                for(var key in obj){
                    // appends relative root folder content
                    $('.wk-ap').append('<div class="' + key + ' root-folder-container"></div>');
                    $('.wk-ap .' + key).html('<div class="root-folder-title"><h2>' + obj[key].name + '</h2><div>');
                    $('.wk-ap .' + key).append('<ul class="folder-list"></ul>');
                    
                    // calls it for append subfolders contents
                    getChildFolders(obj[key].folders, key);
                }
            }
            
            /** 
             *  Gets single folders with relative content
             *  @param  obj (array of folders object)
             *  @param  parent (string) name of parent root folder 
             */
            function getChildFolders(folders, parent){
                // loops each folders
                for(var key in folders){
                    $('.' + parent + ' .folder-list')
                        // appends list tag item
                        .append('<li class="folder-item ' + key + '"><a></a></li>')
                        // find and append, to current list 'a' tag, cover and title
                        .find('.' + key + ' a')
                        .append('<img src="' + folders[key].cover + '" alt="' + key + '" />')
                        .append('<h5>' + folders[key].name + ' (' + Object.keys(folders[key].images).length + ' img)</h5>')
                        // attach to current 'a' its identity attributes
                        .attr('data-parent', parent)
                        .attr('data-folder', key);
                }
            }
            

            /** 
             *  Insert content in overlay element and fadein it when images are loaded
             *  @param  folder (object)
             */
            function populateOverlay(folder){

                // Empty ul element
                $('#wk-overlay .image-list').empty();
                var html_content = '';
                var array_path;
                var image_file_name;
                var file_weight;
                $('#wk-overlay .image-folder-title').html(folder.path);

                // For each image in that folder creates html content
                for(var image in folder.images) {

                    array_path = folder.images[image].public_id.split("/");
                    image_file_name = (array_path[2] + "." + folder.images[image].format)
                    if(image_file_name.length > 20){
                        image_file_name = image_file_name.substring(0, 16) + " ...";
                    }
                    
                    file_weight = niceBytes(folder.images[image].bytes);
                    

                    html_content += 
                        '<li class="image-item">' +
                            '<div class="image-container"><img src="' + folder.images[image].cover + '" alt="' + folder.images[image].public_id + '" />' +
                                '<div class="image-download">' +
                                    '<a href="' + folder.images[image].url + '" download>' +
                                    '<svg viewBox="29 29 142 141"><polygon points="146.7 113.3 146.7 140 53.3 140 53.3 113.3 33.3 113.3 33.3 140 33.3 166.7 53.3 166.7 146.7 166.7 166.7 166.7 166.7 140 166.7 113.3"/><polygon points="120 86 120 33.3 80 33.3 80 86 58.7 86 100 127.3 141.3 86"/></svg>' +
                                    '<span class="image-weight">' + file_weight + '</span>' +
                                    '</a>' + 
                                '</div>' +
                            '</div>' +
                            '<h5 class="image-title">' + image_file_name + '</h5>' +
                        '</li>';
                }
                
                // Appends html content to ul element
                $('#wk-overlay .image-list').append(html_content);
                
                // When the images are loaded, fadein overlay
                $('.image-item img').load(function(evt){
                    $('#wk-overlay').fadeIn();
                });
            }
            
            
            const units = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            function niceBytes(x){

                let l = 0, n = parseInt(x, 10) || 0;
                while(n >= 1024 && ++l)
                    n = n/1024;

                return(n.toFixed(n >= 10 || l < 1 ? 0 : 1) + ' ' + units[l]);
            }



            // Listener for click in folder item event
            $('.folder-item a').on('click', function(){
                var parent = $(this).attr('data-parent');
                var folder = $(this).attr('data-folder');
                
                populateOverlay(obj[parent].folders[folder]);
                
            });
            
            // Listener for click in X event
            $('#wk-overlay .close-overlay').on('click', function(){
                $('#wk-overlay .image-list').empty();
                $('#wk-overlay').fadeOut();
            });

            

        </script>
    </body>
</html>
