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
            color: #333
        }
        a {
            color: #428bca;
            text-decoration: none
        }
        a:hover, a:focus {
            color: #2a6496;
            text-decoration: underline
        }
        a:focus {
            outline: thin dotted #333;
            outline: 5px auto -webkit-focus-ring-color;
            outline-offset: -2px
        }
        #container { 
            width: 900px; 
            margin: 0 auto
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
            background-color: #428bca;
            border: 1px solid #428bca;
            color: #fff
        }
        .collections>a {
            padding: 3px 6px;
        }
        .collections>a.active {

            background-color: #428bca;
            border: 1px solid #428bca;
            color: #fff
        }
        .dbs, .find {
            padding: 20px 6px;
        }
        input[type="text"] {
            border: 1px solid #428bca;
            height: 20px
        }
        input.query {
            width: 300px
        }
        .error {
            font-weight: bold;
            color: #d44950
        }
    </style>
  </head>
  <body>
    <div id="container">
        <div class="dbs">
            <form>
                DBs list
                <select id="db-list" name="db">
                <?php foreach (\globals\vars('db_list') as $db): ?>
                    <option value="<?= $db ?>" <?= isset($_GET['db']) && $db == $_GET['db'] ? 'selected="selected"':'' ?>><?= $db ?></option>
                <?php endforeach ?>
                </select>
                <input type="submit" value="Change DB" />
            </form>
        </div>
        <?php if (\globals\vars('collections')): ?>
        <div class="collections">
            <?php foreach (\globals\vars('collections') as $collection): ?>
                <a href="<?= \utils\query('collection', $collection) ?>" class="<?= isset($_GET['collection']) && $_GET['collection'] == $collection ? 'active':'' ?>"><?= $collection ?></a>
            <?php endforeach ?>
        </div>
        <?php endif ?>
        <?php if (\globals\vars('collection')): ?>
        <div class="find">
            <form>
            <input type="hidden" name="db" value="<?= \globals\vars('db') ?>" />
            <input type="hidden" name="collection" value="<?= \globals\vars('collection') ?>" />
            Query <input type="text" class="query" placeholder='{"userId": 1}' name="find" value='<?= isset($_GET['find']) ? str_replace("'", '"', $_GET['find']):'' ?>' />
            <input type="submit" value="Find" />
            <?php if (\globals\vars('find_error')): ?>
            <span class="error"><?= \globals\vars('find_error') ?></span>
            <?php endif ?>
            </form>
        </div>
        <?php endif ?>
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
  </body>
</html>
<?php } ?>