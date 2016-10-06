<?php

/*
 * Yalig - Copyright 2013 Yosko (www.yosko.net)
 *
 * This file is part of Yalig.
 *
 * Yalig is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Yalig is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Yalig.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once('config.php');

/******************************************/
/**              FUNCTIONS               **/
/******************************************/

//get lists of subdirectories and files from in requested directory
function getGalleryContent($path) {
    $directories = array();
    $files = array();
    if ($handle = opendir($path)) {
        while ($entry = readdir($handle)) {
            if ($entry != '.' && $entry != '..') {
                if (is_dir($path.$entry) && strpos($entry,'.thumb') === false) {
                    $thumb = getFirstImage($path.$entry.'/', true);

                    $directories[] = array(
                        'name' => $entry,
                        'thumb' => $thumb,
                        'date' => filemtime($path.$entry)
                    );
                } elseif (isImage($path.$entry) === true) {
                    $thumb = getThumb($path, $entry);

                    $files[] = array(
                        'name' => $entry,
                        'thumb' => $thumb,
                        'date' => filemtime($path.$entry)
                    );
                }
            }
        }
    }

    if (SORT_TYPE == 'name') {
        if (SORT_ORDER_ASC) {
            sort($directories);
            sort($files);
        } else {
            rsort($directories);
            rsort($files);
        }
    } elseif (SORT_TYPE == 'name') {
        uasort($directories, dateCompare($a, $b) );
        uasort($files, dateCompare($a, $b) );
    }

    return array(
        "directories" => $directories,
        "files" => $files
    );
}

function dateCompare($a, $b) {
    if (SORT_ORDER_ASC) {
        return $a['date'] > $b['date'];
    } else {
        return $a['date'] < $b['date'];
    }
}

//check if a path is a file AND an image (through getimagesize and image types)
function isImage($path) {
    if (is_file($path)) {
        $imageInfo = getimagesize($path);
        $imageType = $imageInfo[2];
        if (in_array($imageType , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP))) {
            return true;
        }
    }
    return false;
}

//try to find an image in a subdirectory to illustrate it
//if needed, will create the thumbnail for that image too
function getFirstImage($path, $getThumb = true) {
    if ($handle = opendir($path)) {
        while ($entry = readdir($handle)) {
            if (isImage($path.$entry) === true) {
                if ($getThumb === true) {
                    $thumb = getThumb($path, $entry);
                } else {
                    $thumb = $path.'/'.$entry;
                }
                return $thumb;
            }
        }
    }
    return null;
}

//returns the thumbnail path if it exists
//if not, returns the path to the original image
function getThumb($path, $file) {
    $thumbPath = preg_replace('%'.GALLERY_PATH.'%', GALLERY_PATH.THUMB_SUBDIRECTORY, $path, 1);
    $thumbFile = pathinfo($file, PATHINFO_FILENAME).'.jpg';

    if (is_file($thumbPath.$thumbFile)) {
        $source = $thumbPath.$thumbFile;
    } else {
        //try to create the thumb or else return the image itself
        if (createThumb($path.$file, $thumbPath.$thumbFile) === true) {
            $source = $thumbPath.$thumbFile;
        } else {
            $source = $path.$file;
        }
    }

    return $source;
}

//create a thumbnail for a given image
function createThumb($sourceFile, $thumbFile) {
    list($width, $height) = getimagesize($sourceFile);
    if (CREATE_THUMB && ($width > THUMB_WIDTH || $height > THUMB_HEIGHT)) {

        //creating a thumbnail from a big image can be quite demanding
        //won't crash if ini_set not enabled
        ini_set('memory_limit', '128M');

        //create thumb dir and subdir
        if (!is_dir(dirname($thumbFile)))
            mkdir(dirname($thumbFile), 0775, true);

        //open source image
        $imageInfo = getimagesize($sourceFile);
        $imageType = $imageInfo[2];
        if ($imageType == IMAGETYPE_GIF) {
            $image = imagecreatefromgif ($sourceFile);
        } elseif ($imageType == IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg($sourceFile);
        } elseif ($imageType == IMAGETYPE_PNG) {
            $image = imagecreatefrompng($sourceFile);
        } elseif ($imageType == IMAGETYPE_BMP) {
            //won't work as BMP isn't supported in GD
            $image = imagecreatefromjpeg($sourceFile);
        }

        //prepare thumbnail
        $thumbnail = imagecreatetruecolor(THUMB_WIDTH, THUMB_HEIGHT);

        //chack ratio
        if ($width/$height < THUMB_WIDTH/THUMB_HEIGHT) {
            //"smallest" is width (relatively)
            $croppedHeight = ($width*THUMB_HEIGHT)/THUMB_WIDTH;
            $cropY = ($height-$croppedHeight)/2;
            imagecopyresampled($thumbnail, $image, 0, 0, 0, $cropY, THUMB_WIDTH, THUMB_HEIGHT, $width, $croppedHeight);
        } else {
            //"smallest" is height (relatively)
            $croppedWidth = ($height*THUMB_WIDTH)/THUMB_HEIGHT;
            $cropX = ($width-$croppedWidth)/2;
            imagecopyresampled($thumbnail, $image, 0, 0, $cropX, 0, THUMB_WIDTH, THUMB_HEIGHT, $croppedWidth, $height);
        }

        imagedestroy($image);

        //save thumbnail file with a JPEG quality of 80%
        imagejpeg($thumbnail, $thumbFile, 80);
        imagedestroy($thumbnail);

        return true;
    }
    return false;
}


/******************************************/
/**             EXECUTE PAGE             **/
/******************************************/

//get "current" path
$display = '';
$relativePath = '';
$path = GALLERY_PATH;
if (isset($_GET['path']) && strpos($_GET['path'],'..') === false) {
    $relativePath = trim($_GET['path']);
    $path = GALLERY_PATH.$relativePath;
}

//determine kind of display
if (is_dir($path)) {
    if (strlen($path) > strlen(GALLERY_PATH) && substr($path, -1) != '/') {
        $relativePath .= '/';
        $path .= '/';
    }
    $display = 'directory';
    $gallery = getGalleryContent($path);
} elseif (isImage($path)) {
    if (USE_DIRECT_LINK === true) {
        header("Location: ".$path);
        die();
    } else {
        $display = 'image';
    }
} else {
    $relativePath = 'error';
    $display = 'error';
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php echo GALLERY_TITLE; ?></title>
<?php if (USE_JS === true) { ?>

    <script type="text/javascript" src="js/jquery-1.9.0.min.js"></script>
    <link rel="stylesheet" href="js/jquery.fancybox.css" type="text/css" media="screen" />
    <script type="text/javascript" src="js/jquery.fancybox.pack.js"></script>
    <link rel="stylesheet" href="js/helpers/jquery.fancybox-thumbs.css" type="text/css" media="screen" />
    <script type="text/javascript" src="js/helpers/jquery.fancybox-thumbs.js"></script>
    <script type="text/javascript">
$(document).ready(function() {
    $(".fancybox").fancybox({
        openEffect	: 'none',
		closeEffect	: 'none',
		prevEffect	: 'none',
		nextEffect	: 'none',
        minWidth    : 16,
        minHeight   : 16,
        padding     : 5,
        arrows      : false,
		helpers	: {
			thumbs	: {
				width	: <?php echo THUMB_WIDTH / 2; ?>,
				height	: <?php echo THUMB_HEIGHT / 2; ?>
			},
            overlay : {
                css : {
                    'background' : 'rgba(0, 0, 0, 0.8)'
                }
            }
		}
	});
});
    </script>

<?php } ?>
    <link rel="stylesheet" href="style.css" type="text/css" media="screen" />
    <style>
/* global dynamic styles depending on PHP values */
.thumbs a { width:<?php echo THUMB_WIDTH; ?>px; height:<?php echo THUMB_HEIGHT; ?>px; }
.thumbs img { max-width:<?php echo THUMB_WIDTH; ?>px; max-height:<?php echo THUMB_HEIGHT; ?>px; }
    </style>
</head>
<body>
    <h1><?php echo GALLERY_TITLE; ?></h1>
    <div id="content">
        <div id="breadcrumbs">
            <a href="?">Home</a> ► <?php
$dirs = preg_split('-/-', $relativePath, -1, PREG_SPLIT_NO_EMPTY);
$nbDirs = count($dirs);
$dirPath = '';
for ($i = 0; $i < $nbDirs-1; $i++) {
    $dirPath .= $dirs[$i].'/';
    echo '<a href="?path='.$dirPath.'">'.$dirs[$i].'</a> ► ';
}
if ($nbDirs > 0)
    echo $dirs[$nbDirs-1];
?>

        </div>
<?php if ($display == 'directory') { ?>
        <ul class="thumbs">
<?php
sort($gallery['directories']);
foreach ($gallery['directories'] as $directory) {
?>
            <li class="dir">
                <a href="?path=<?php echo $relativePath.$directory['name']; ?>" title="Last edit: <?php echo date("Y-m-d H:i:s", $directory['date']); ?>">
                    <span class="dirName"><?php echo $directory['name']; ?></span>
                    <img src="<?php echo $directory['thumb']; ?>" />
                </a>
            </li>
<?php
}
foreach ($gallery['files'] as $file) { ?>
            <li class="img">
                <a href="<?php echo (USE_DIRECT_LINK === true) ? $path.$file['name'] : '?path='.$relativePath.$file['name']; ?>" title="<?php echo $file['name']."\r"; ?> &bull; last edit: <?php echo date("Y-m-d H:i:s", $file['date']); ?>" class="fancybox" rel="fancybox">
                    <img src="<?php echo $file['thumb']; ?>" />
                </a>
            </li>
<?php } ?>
        </ul>
        <!--<div id="manager">
            <a href="">TODO</a>
        </div>-->
<?php } elseif ($display == 'image') { ?>
        <div class="fullImg">
            <a href="<?php echo $path; ?>">
                <img src="<?php echo $path; ?>" />
            </a>
        </div>
<?php } elseif ($display == 'error') { ?>
        <div class="error">The page you are looking for doesn&apos;t seem to exist.</div>
<?php } ?>
    </div>
    <div id="footer">
    <acronym title="Yet Another Lightweight Image Gallery">Yalig</acronym> v1.0 by <a href="http://www.yosko.net">Yosko</a>
    <?php if (USE_JS === true) { ?>&bull; using <a href="http://fancyapps.com/fancybox">FancyBox</a>
    <?php } ?>
    </div>
</body>