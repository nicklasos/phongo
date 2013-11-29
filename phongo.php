<?php

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

function mongo($db = null)
{
    static $mongo;

    if (!isset($mongo)) {
        $mongo = new Mongo;
    }

    if ($db) {
        $mongo = $mongo->selectDB($db);
    }

    return $mongo;
}

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

function global_vars($name, $value = null)
{
    static $vars = [];
    if ($value) $vars[$name] = $value;
    return isset($vars[$name]) ? $vars[$name] : null;
}

function current_page()
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

function page_params($count)
{
    $limit = 2;
    $page = current_page();
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
        $html = '<select id="pagination">';

        for ($p = 1; $p <= ($pages + 1); $p++) {
            $selected = $current == $p ? 'selected="selected"' : '';
            $html .= "<option {$selected}>{$p}</li>";
        }

        $html .= '</select>';
    }

    return $html;
}

function set_dbs_list()
{
    $db_list = mongo()->admin->command(['listDatabases' => 1]);

    $db_list = array_map(
        function ($i)
        {
            return $i['name'];
        },
        $db_list['databases']
    );

    global_vars('db_list', $db_list);
}

function change_db()
{
    if (isset($_GET['db'])) {
        mongo($_GET['db']);
        global_vars('db', $_GET['db']);
        global_vars(
            'collections',
            array_map(
                function ($i)
                {
                    return preg_replace('/^(.*)\./', '', $i);
                },
                mongo()->listCollections()
            )
        );
    }
}

function find()
{
    if (isset($_GET['collection'])) {
        $collection = global_vars('collection', $_GET['collection']);
        $find = [];

        if (isset($_GET['find']) && \strlen($_GET['find']) > 0) {
            $find = json_decode(str_replace("'", '"', $_GET['find']));
            if (!$find) {
                $find = [];
                global_vars('find_error', 'Error in query!');
            }

        }

        if (!global_vars('find_error')) {
            $page = page_params(mongo()->$collection->find($find)->count());
            global_vars('pagination', pagination($page['pages'], $page['current']));
            global_vars(
                'find',
                mongo()
                    ->$collection
                    ->find($find)
                    ->sort(['_id' => 1])
                    ->skip($page['skip'])
                    ->limit($page['limit'])
            );
        }
    }
}

set_dbs_list();
change_db();
find();

?>
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
        .pages {
            margin-left: 20px;
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
        .form-error {
            float: right; padding-right: 30px;
        }
        #header {
            font-weight: bold;
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
                <?php foreach (global_vars('db_list') as $db): ?>
                    <option value="<?= $db ?>" <?= isset($_GET['db']) && $db == $_GET['db'] ? 'selected="selected"':'' ?>><?= $db ?></option>
                <?php endforeach ?>
                </select>
            </span>
            <span class="collections">
            <?php if (global_vars('collections')): ?>
                Collection
                <select id="collections">
                    <option></option>
                    <?php foreach (global_vars('collections') as $collection): ?>
                    <option <?= isset($_GET['collection']) && $_GET['collection'] == $collection ? 'selected':'' ?>>
                        <?= $collection ?>
                    </option>
                    <?php endforeach ?>
                </select>
            <?php endif ?>
            </span>
            <?php if ($pagination = global_vars('pagination')): ?>
                <span class="pages">
                    Page <?= $pagination ?>
                </span>
            <?php endif ?>
        </div>
        <div class="form-error">
            <?php if (global_vars('collection')): ?>
            <form>
                <?php if (global_vars('find_error')): ?>
                <span class="error"><?= global_vars('find_error') ?></span>
                <?php endif ?>
                <input type="hidden" name="db" value="<?= global_vars('db') ?>" />
                <input type="hidden" name="collection" value="<?= global_vars('collection') ?>" />
                <input type="text" class="query" placeholder='{"userId": 1}' name="find" value='<?= isset($_GET['find']) ? str_replace("'", '"', $_GET['find']):'' ?>' />
            </form>
        <?php endif ?>
        </div>
        <div style="clear: both"></div>
    </div>
    <div id="container">

        <div>
        <?php if (global_vars('find')): ?>
            <?php foreach (global_vars('find') as $f): ?>
                <pre><?= json_encode($f, JSON_PRETTY_PRINT) ?></pre>
            <?php endforeach ?>
        <?php endif ?>
        </div>
    </div>
    <script>
        (function () {
            document.getElementById('db-list').onchange = function () {
                console.log('1');
                document.location = '?db=' + this.options[this.selectedIndex].value;
            };

            document.getElementById('collections').onchange = function () {
                var collection = this.options[this.selectedIndex].value,
                    d = document.getElementById('db-list'),
                    db = d.options[d.selectedIndex].value;

                document.location = '?db=' + db + (collection ? '&collection=' + collection : '');
            };

            document.getElementById('pagination').onchange = function () {
                var page = this.options[this.selectedIndex].value,
                    d = document.getElementById('db-list'),
                    db = d.options[d.selectedIndex].value,
                    c = document.getElementById('collections'),
                    collection = c.options[c.selectedIndex].value;

                document.location = '?db=' + db+ '&collection=' + collection + '&page=' + page;
            };
        }());
    </script>
  </body>
</html>
