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

define( "GALLERY_TITLE",      "Yalig" );

// Use Thumbnails
define( "CREATE_THUMB",       true );
define( "THUMB_WIDTH",        120 );
define( "THUMB_HEIGHT",       90 );

// Use Fancybox (with jQuery) to display images as nice overlay popups
define( "USE_JS",             true );

/* 
 * if true, links on thumbs will point directly to the image
 * if false, will point to a webpage showing the image
 * Must be true if USE_JS is true
 */
define( "USE_DIRECT_LINK",    true );

/* 
 * SYSTEM
 * Don't touch anything beyond this point
 * unless you know what you are doing
 */
define( "GALLERY_PATH",       "img/" );
define( "THUMB_SUBDIRECTORY", ".thumb/" );

?>