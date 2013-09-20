<?php

namespace db
{
    function mongo($db = null)
    {
        static $mongo;

        if (!isset($mongo)) {
            $mongo = new \Mongo;
        }

        if ($db) {
            $mongo = $mongo->selectDB($db);
        }

        return $mongo;
    }
}

namespace utils
{
    function wtf($what)
    {
        echo '<pre>';
        print_r($what);
        echo '</pre>';
    }

    function query($param = '', $change = '')
    {
        $get_params = $_GET;

        if ($param != 'page') {
            unset($get_params['page']);
            unset($get_params['find']);
        }

        if ($param && $change) {
            $get_params[$param] = $change;
        }

        return urldecode('?' . http_build_query($get_params));
    }
}

namespace globals
{
    function vars($name, $value = null)
    {
        static $vars = array();
        if ($value) $vars[$name] = $value;
        return isset($vars[$name]) ? $vars[$name] : null;
    }
}

namespace page
{
    function current()
    {
        $page = 1;
        if (isset($_GET['page'])) {
            $page = intVal($_GET['page']);

            if ($page <= 0) {
                $page = 1;
            }
        }

        return $page;
    }

    function params($count)
    {
        $limit = 30;
        $page = current();
        $skip = $limit * ($page - 1);

        if ($skip >= $count) {
            $skip = 0;
        }

        $pages = ceil($count / $limit);

        return [
            'current' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'skip' => $skip
        ];
    }

    function pagination($pages, $current)
    {
        $html = '';

        if ($pages > 1) {
            $html = '<ul class="pagination">';
          
            for ($p = 1; $p <= $pages; $p++) {
                $class = $current == $p ? 'active':'';
                $query = \utils\query('page', $p);

                $html .= "<li class='{$class}'><a href='{$query}'>{$p}</a></li>";
            }

            $html .= '</ul>';
        }

        return $html;
    }
}

namespace app
{
    function init()
    {
        error_reporting(E_ALL);
        ini_set('error_reporting', E_ALL);
    }

    function set_dbs_list()
    {
        $db_list = \db\mongo()->admin->command(array('listDatabases' => 1));
        $db_list = array_map(function ($i) { return $i['name']; }, $db_list['databases']);
        \globals\vars('db_list', $db_list);
    }

    function change_db()
    {
        if (isset($_GET['db'])) {
            \db\mongo($_GET['db']);
            \globals\vars('db', $_GET['db']);
            \globals\vars('collections', array_map(function ($i) { return preg_replace('/^(.*)\./', '', $i); }, \db\mongo()->listCollections()));
        }
    }

    function find()
    {
        if (isset($_GET['collection'])) {
            $collection = \globals\vars('collection', $_GET['collection']);
            $find = array();

            if (isset($_GET['find']) && \strlen($_GET['find']) > 0) {
                $find = json_decode(str_replace("'", '"', $_GET['find']));
                if (!$find) {
                    $find = array();
                    \globals\vars('find_error', 'Error in query!');
                }

            }

            if (!\globals\vars('find_error')) {
                $page = \page\params(\db\mongo()->$collection->find($find)->count());
                \globals\vars('pagination', \page\pagination($page['pages'], $page['current']));
                \globals\vars('find', \db\mongo()->$collection->find($find)->sort(array('_id' => -1))->skip($page['skip'])->limit($page['limit']));
            }
        }
    }

    function run()
    {
        init();
        set_dbs_list();
        change_db();
        find();
    }

    run();
}

namespace template { ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lite MongoDB explorer">
    <meta name="author" content="Nicklasos">
    <style>
        body {
            font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
            font-size: 14px;
            line-height: 1.428571429;
            color: #333;
            margin: 0;
        }
        a {
            color: gray;
            text-decoration: none
        }
        a:hover, a:focus {
            color: black;
            text-decoration: underline
        }
        a:focus {
            outline: thin dotted #333;
            outline: 5px auto -webkit-focus-ring-color;
            outline-offset: -2px
        }
        #container { 
            min-width: 300px;
            width: 900px; 
            margin: 60px 0 0 50px;
        }
        .pagination {
            display: inline-block;
            padding-left: 0;
        }
        .pagination>li {
            display: inline;
        }
        .pagination>li>a {
            padding: 3px 6px;
            margin-left: -1px;
            text-decoration: none;
            background-color: #fff;
            border: 1px solid #ddd
        }
        .pagination>li.active>a {
            background-color: gray;
            border: 1px solid gray;
            color: #fff
        }
        .collections>a {
            padding: 3px 6px;
        }
        .collections>a.active {

            background-color: gray;
            border: 1px solid gray;
            color: white;
        }
        .dbs {
            padding-right: 20px;
        }
        input[type="text"] {
            border: none;
            height: 22px
        }
        input.query {
            background: #131313;
            border: 0;
            height: 23px;
            color: white;
            font-size: 15px;
            border-radius: 0px;
            border-bottom: 1px solid #3f3f3f;
            width: 300px;
            padding-left: 5px;
        }
        .error {
            font-weight: bold;
            color: #d44950
        }
        #header {
            background-color: #222222;
            min-width: 670px;
            width: 100%;
            top: 0;
            position: fixed;
            color: white;
            padding: 10px;
            border-bottom: 1px solid gray;
        }
        .logo {
            font-weight: bold;
            margin-right: 30px;
        }
    </style>
  </head>
  <body>
    <div id="header">
        <div style="float: left">
            <span class="logo">Phongo</span>
            <span class="dbs">
                DB
                <select id="db-list" name="db">
                <?php foreach (\globals\vars('db_list') as $db): ?>
                    <option value="<?= $db ?>" <?= isset($_GET['db']) && $db == $_GET['db'] ? 'selected="selected"':'' ?>><?= $db ?></option>
                <?php endforeach ?>
                </select>
            </span>
            <span class="collections">
            <?php if (\globals\vars('collections')): ?>
                Collection
                <select id="collections">
                    <option></option>
                <?php foreach (\globals\vars('collections') as $collection): ?>
                    <option <?= isset($_GET['collection']) && $_GET['collection'] == $collection ? 'selected':'' ?> href="<?= \utils\query('collection', $collection) ?>"><?= $collection ?></option>
                <?php endforeach ?>
                </select>
            <?php endif ?>
            </span>
        </div>
        <div style="float: right; padding-right: 30px">
            <?php if (\globals\vars('collection')): ?>
            <form>
                <?php if (\globals\vars('find_error')): ?>
                <span class="error"><?= \globals\vars('find_error') ?></span>
                <?php endif ?>
                <input type="hidden" name="db" value="<?= \globals\vars('db') ?>" />
                <input type="hidden" name="collection" value="<?= \globals\vars('collection') ?>" />
                <input type="text" class="query" placeholder='{"userId": 1}' name="find" value='<?= isset($_GET['find']) ? str_replace("'", '"', $_GET['find']):'' ?>' />
            </form>
        <?php endif ?>
        </div>
        <div style="clear: both"></div>
    </div>
    <div id="container">
        
        <div>
        <?php if (\globals\vars('find')): ?>
            <?= \globals\vars('pagination') ?>
            <?php foreach (\globals\vars('find') as $f): ?>
                <pre><?= json_encode($f, JSON_PRETTY_PRINT) ?></pre>
            <?php endforeach ?>
            <?= \globals\vars('pagination') ?>
        <?php endif ?>
        </div>
    </div>
    <script>
        (function () {
            document.getElementById('db-list').onchange = function () {
                document.location = '?db=' + this.options[this.selectedIndex].value;
            };

            document.getElementById('collections').onchange = function () {
                document.location = '?db=' + document.getElementById('db-list').options[document.getElementById('db-list').selectedIndex].value + '&collection=' + this.options[this.selectedIndex].value;
            };
        }());
    </script>
  </body>
</html>
<?php } ?>