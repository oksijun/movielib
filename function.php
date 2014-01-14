<?PHP
/* #############
 * # FUNCTIONS #
 */#############

/* ########################
 * # Connect to databaase #
 */########################
function connect($mysql_ml) {
    $conn_ml = @mysql_connect($mysql_ml[0] . ':' . $mysql_ml[1], $mysql_ml[2], $mysql_ml[3]);
    if (!$conn_ml) {
        die(mysql_error());
    }
    $sel_ml = @mysql_select_db($mysql_ml[4]);
    if (!$sel_ml) {
        die(mysql_error());
    }

    // Sets utf8 connections
    mysql_query('SET CHARACTER SET utf8');
    mysql_query('SET NAMES utf8');
}

/* ##############################
 * # Get settings from database #
 */##############################
function get_settings($mysql_ml, $mysql_tables) {
    
    // if settings in session not exists get it from database
    if (!isset($_SESSION['site_name'])) {
        $set_sql = 'SELECT * FROM ' . $mysql_tables[1];
        $set_result = mysql_query($set_sql);
        while ($set = mysql_fetch_assoc($set_result)) {
            foreach($set as $key => $val) {
                $_SESSION[$key] = $val;
            }
        }
    }
    
    // settings from session to var
    $output_set = array();
    foreach ($_SESSION as $key => $val) {
        $output_set[$key] = $val;
    }
    return $output_set;
}

/* ######################
 * # Create empty table #
 */######################
function create_table($mysql_table, $lang) {
        
    // drop tables
    foreach ($mysql_table as $table) {
        $drop_table_sql = 'DROP TABLE IF EXISTS `' . $table . '`';
        if (!@mysql_query($drop_table_sql)) {
            die(mysql_error());
        }
    }
    
    // table movie
    $create_movies_sql = 'CREATE TABLE `' . $mysql_table[0] . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(100),
                `plot` text,
                `rating` text,
                `year` text,
                `trailer` text,
                `runtime` text,
                `genre` text,
                `director` text,
                `originaltitle` text,
                `country` text,
                `v_codec` text,
                `v_aspect` float DEFAULT NULL,
                `v_width` int(11) DEFAULT NULL,
                `v_height` int(11) DEFAULT NULL,
                `v_duration` int(11) DEFAULT NULL,
                `a_codec` text,
                `a_chan` int(11) DEFAULT NULL,
                `play_count` int(11) DEFAULT NULL,
                `last_played` text DEFAULT NULL,
                `date_added` text DEFAULT NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8';
    if (!@mysql_query($create_movies_sql)) {
        die($lang['inst_could_create'] . ': ' . $mysql_table[0] . ' - ' . mysql_error() . '<br/>');
    }
    
    // table config
    $create_config_sql = 'CREATE TABLE `' . $mysql_table[1] . '` (
                `site_name` varchar(30) DEFAULT "MovieLib",
                `language` varchar(15) DEFAULT "' . $_SESSION['install_lang'] . '",
                `theme` varchar(15) DEFAULT "default",
                `per_page` int(5) DEFAULT 50,
                `panel_top_limit` int(5) DEFAULT 10,
                `panel_top_time` int(5) DEFAULT 5,
                `panel_top` int(1) DEFAULT 1,
                `watched_status` int(1) DEFAULT 1,
                `panel_overall` int(1) DEFAULT 1,
                `panel_genre` int(1) DEFAULT 1,
                `panel_year` int(1) DEFAULT 1,
                `panel_country` int(1) DEFAULT 1,
                `panel_v_codec` int(1) DEFAULT 1,
                `panel_a_codec` int(1) DEFAULT 1,
                `panel_a_chan` int(1) DEFAULT 1,
                `show_fanart` int(1) DEFAULT 1,
                `show_trailer` int(1) DEFAULT 1,
                `protect_site` int(1) DEFAULT 0,
                `token` varchar(6) DEFAULT ""
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8';
    if (!@mysql_query($create_config_sql)) {
        die($lang['inst_could_create'] . ': ' . $mysql_table[1] . ' - ' . mysql_error() . '<br/>');
    }
    if (@mysql_num_rows(mysql_query('SELECT * FROM ' . $mysql_table[1])) == 0) {
        $insert_config_sql = 'INSERT INTO `' . $mysql_table[1] . '` () VALUES ()';
        mysql_query ($insert_config_sql);
    }
    
    // table users
    $create_users_sql = 'CREATE TABLE `' . $mysql_table[2] . '` (
                `id` int(2) NOT NULL AUTO_INCREMENT,
                `login` varchar(5) DEFAULT NULL,
                `password` varchar(32) DEFAULT NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8';
    if (!@mysql_query($create_users_sql)) {
        die($lang['inst_could_create'] . ': ' . $mysql_table[2] . ' - ' . mysql_error() . '<br/>');
    }
    if (@mysql_num_rows(mysql_query('SELECT * FROM ' . $mysql_table[2])) == 0) {
        $insert_users_sql = 'INSERT INTO `' . $mysql_table[2] . '` (`id`, `login`, `password`) VALUES ("", "admin", "21232f297a57a5a743894a0e4a801fc3")';
        mysql_query($insert_users_sql);
        $insert_users_sql = 'INSERT INTO `' . $mysql_table[2] . '` (`id`, `login`, `password`) VALUES ("", "user", "ee11cbb19052e40b07aac0ca060c23ee")';
        mysql_query($insert_users_sql);
    }
}

/* ################################
 * # SYNC - show movie id from db #
 */################################
function show_id($mysql_ml, $mysql_tables) {
    $show_id_sql = 'SELECT id FROM ' . $mysql_tables[0];
    $show_id_result = mysql_query($show_id_sql);
    while ($id = mysql_fetch_array($show_id_result)) {
        echo $id[0] . ' ';
    }
}
 
/* ##########################
 * # SYNC - add Movie to DB #
 */##########################
function sync_add($mysql_ml, $mysql_tables) {
    
    $insert_sql = 'INSERT INTO `' . $mysql_tables[0] . '` (
        `id`,
        `title`,
        `plot`,
        `rating`,
        `year`,
        `trailer`,
        `runtime`,
        `genre`,
        `director`,
        `originaltitle`,
        `country`,
        `v_codec`,
        `v_aspect`,
        `v_width`,
        `v_height`,
        `v_duration`,
        `a_codec`,
        `a_chan`,
        `play_count`,
        `last_played`,
        `date_added`
      ) VALUES ';

    $insert_sql.= '(
        "' . $_POST['id'] . '",
        "' . addslashes($_POST['title']) . '",
        "' . addslashes($_POST['plot']) . '",
        "' . round($_POST['rating'], 1) . '",
        "' . $_POST['year'] . '",
        ' . ($_POST['trailer'] == '' ? 'NULL' : '"' . addslashes($_POST['trailer']) . '"') . ',
        '  . $_POST['runtime'] . ',
        "' . addslashes($_POST['genre']) . '",
        "' . addslashes($_POST['director']) . '",
        "' . addslashes($_POST['originaltitle']) . '",
        "' . addslashes($_POST['country']) . '",
        "' . addslashes($_POST['v_codec']) . '",
        "' . addslashes($_POST['v_aspect']) . '",
        "' . addslashes($_POST['v_width']) . '",
        "' . addslashes($_POST['v_height']) . '",
        "' . addslashes($_POST['v_duration']) . '",
        "' . addslashes($_POST['a_codec']) . '",
        "' . addslashes($_POST['a_chan']) . '",
        "' . addslashes($_POST['playcount']) . '",
        ' . ($_POST['lastplayed'] == '' ? 'NULL' : '"' . addslashes($_POST['lastplayed']) . '"') . ',
        "' . addslashes($_POST['dateadded']) . '"
    )';
    
    $insert = mysql_query($insert_sql);

    if (!$insert) {
        echo 'ERROR: MySQL - ' . mysql_error();
    } else {
    
        // poster
        if (substr($_POST['poster'], 0, 4) == 'http') {
            gd_convert('cache/' . $_POST['id'] . '.jpg', $_POST['poster'], 140, 198);
        } else {
            $fp = fopen('cache/temp_' . $_POST['id'], 'w');
            fwrite($fp, $_POST['poster']);
            fclose($fp);
            gd_convert('cache/' . $_POST['id'] . '.jpg', 'cache/temp_' . $_POST['id'], 140, 198);
            unlink('cache/temp_' . $_POST['id']);
        }
        
        // fanart
        if (substr($_POST['fanart'], 0, 4) == 'http') {
            gd_convert('cache/' . $_POST['id'] . '_f.jpg', $_POST['fanart'], 1280, 720);
        } else {
            $fp = fopen('cache/temp_f' . $_POST['id'], 'w');
            fwrite($fp, $_POST['fanart']);
            fclose($fp);
            gd_convert('cache/' . $_POST['id'] . '_f.jpg', 'cache/temp_f' . $_POST['id'], 1280, 720);
            unlink('cache/temp_f' . $_POST['id']);
            
        }
    }
}

/* ###############################
 * # SYNC - remove Movie from DB #
 */###############################
function sync_remove($mysql_ml, $mysql_tables) {
    
    $id = $_POST['id'];
    $delete_movie_sql = 'DELETE FROM ' . $mysql_tables[0] . ' WHERE id = "' . $id . '"';
    if (file_exists('cache/' . $id . '.jpg')) {
        unlink('cache/' . $id . '.jpg');
    }
    if (file_exists('cache/' . $id . '_f.jpg')) {
        unlink('cache/' . $id . '_f.jpg');
    }
    
    $delete = mysql_query($delete_movie_sql);
    
    if (!$delete) {
        echo 'ERROR: MySQL - ' . mysql_error();
    }
}

/* ##################
 * # SYNC - Watched #
 */##################
function sync_watched($mysql_ml, $mysql_tables) {
    
    $update_sql = 'UPDATE `' . $mysql_tables[0] . '` SET 
        play_count = "' . addslashes($_POST['playcount']) . '",
        last_played = "' . addslashes($_POST['lastplayed']) . '",
        date_added = "' . addslashes($_POST['dateadded']) . '"
        WHERE id = "' . $_POST['id'] . '"';

    $update = mysql_query($update_sql);

    if (!$update) {
        echo 'ERROR: MySQL - ' . mysql_error();
    }
}

/* ####################
 * # SYNC - unWatched #
 */####################
function sync_unwatched($mysql_ml, $mysql_tables) {
    
    $update_sql = 'UPDATE `' . $mysql_tables[0] . '` SET 
        play_count = NULL,
        last_played = NULL,
        date_added = "' . addslashes($_POST['dateadded']) . '"
        WHERE id = "' . $_POST['id'] . '"';
        
    $update = mysql_query($update_sql);

    if (!$update) {
        echo 'ERROR: MySQL - ' . mysql_error();
    }
}

/* #####################
 * # SYNC - Lastplayed #
 */#####################
function sync_lastplayed($mysql_ml, $mysql_tables) {
    
    $update_sql = 'UPDATE `' . $mysql_tables[0] . '` SET 
        play_count = "' . addslashes($_POST['playcount']) . '",
        last_played = "' . addslashes($_POST['lastplayed']) . '"
        WHERE id = "' . $_POST['id'] . '"';

    $update = mysql_query($update_sql);

    if (!$update) {
        echo 'ERROR: MySQL - ' . mysql_error();
    }
}

/* ################
 * # Change Token #
 */################
function change_token($mysql_tables) {
    $array = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',0,1,2,3,4,5,6,7,8,9);
    $new_token = '';
    for ($i = 1; $i <= 6; $i++) {
        $new_token.= $array[array_rand($array)];
    }
    $update_sql = 'UPDATE `' . $mysql_tables[1] . '` SET token = "' . $new_token . '"';
    $update = mysql_query($update_sql);
    unset($_SESSION['site_name']);
    return $new_token;
}

/* #################
 * # GD conversion #
 */#################
function gd_convert($cache_path, $img_link, $new_width, $new_height) {
    if (!file_exists($cache_path) and !empty($img_link)) {
        $img = @imagecreatefromjpeg($img_link);
        if ($img) {
            $width = imagesx($img);
            $height = imagesy($img);
            $img_temp = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($img_temp, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($img_temp, $cache_path, 80);
        }
    }
}
?>