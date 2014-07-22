<?php

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

function vars($name, $value = null)
{
    static $vars = [];

    if ($value) {
        $vars[$name] = $value;
    }

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
    $limit = 50;
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
        $max = vars('pages_count');
        $html .= "<input type='number' id='page' class='page' min='0' max='{$max}' name='page' value='{$current}' /> / " . vars('pages_count');
    }

    return $html;
}

function set_dbs_list()
{
    $db_list = mongo()->admin->command(['listDatabases' => 1]);

    // Why not array_column? Because fuck you, that's why!
    $db_list = array_map(
        function ($i) {
            return $i['name'];
        },
        $db_list['databases']
    );

    vars('db_list', $db_list);
}

function change_db()
{
    if (isset($_GET['db'])) {
        mongo($_GET['db']);
        vars('db', $_GET['db']);
        vars(
            'collections',
            array_map(
                function ($i) {
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
        $collection = vars('collection', $_GET['collection']);
        $find = [];

        if (isset($_GET['find']) && strlen($_GET['find']) > 0) {
            $find = json_decode(str_replace("'", '"', $_GET['find']), true);
            if (!$find || !is_array($find)) {
                vars('find_error', 'Error in query!');
            }

        }

        if (!vars('find_error')) {
            $count = mongo()->selectCollection($collection)->count($find);
            $page = page_params($count);


            $query = mongo()
                ->selectCollection($collection)
                ->find($find)
                ->sort(['_id' => -1])
                ->skip($page['skip'])
                ->limit($page['limit']);

            vars('item_count', $count);
            vars('pages_count', $page['pages']);
            vars('pagination', pagination($page['pages'], $page['current']));
            vars(
                'find',
                $query
            );
        }
    }
}

function indexes()
{
    if (vars('collection')) {
        vars('indexes', mongo()->selectCollection(vars('collection'))->getIndexInfo());
    }
}

error_reporting(0);
ini_set('error_reporting', 0);

set_dbs_list();
change_db();
find();
indexes();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lite MongoDB explorer">
    <meta name="author" content="Nicklasos">
    <link href="data:image/x-icon;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAtHt3jbR7d/+0e3f/tHt3/7R7d/+0e3f/tHt3vTSGG700hhv/NIYb/zSGG/80hhv/NIYb/zSGG4cAAAAAtHt3jbR7d/+0e3f/tHt3/7R7d/+0e3f/tHt3jbR7d700hhu9NIYbhzSGG/80hhv/NIYb/zSGG/80hhv/NIYbh7R7d/+0e3f/tHt3/7R7d/+0e3f/tHt3P7R7dz+0e3e9NIYbvTSGGzs0hhs7NIYb/zSGG/80hhv/NIYb/zSGG/+0e3f/tHt3/7R7d/+0e3f/tHt3P7R7dz+0e3c/tHt3vTSGG700hhs7NIYbOzSGGzs0hhv/NIYb/zSGG/80hhv/tHt3/7R7d/+0e3f/tHt3P7R7dz+0e3c/tHt3P7R7d700hhu9NIYbOzSGGzs0hhs7NIYbOzSGG/80hhv/NIYb/7R7d/+0e3f/tHt3/7R7dz+0e3c/tHt3P7R7dz+0e3e9NIYbvTSGGzs0hhs7NIYbOzSGGzs0hhv/NIYb/zSGG/+0e3f/tHt3/7R7d/+0e3eNtHt3P7R7dz+0e3c/tHt3vTSGG700hhs7NIYbOzSGGzs0hhuHNIYb/zSGG/80hhv/tHt3/7R7d/+0e3f/tHt3jbR7dz+0e3c/tHt3P7R7d700hhu9NIYbOzSGGzs0hhs7NIYbhzSGG/80hhv/NIYb/7R7d/+0e3f/tHt3/7R7d/+0e3c/tHt3P7R7dz+0e3e9NIYbvTSGGzs0hhs7NIYbOzSGG/80hhv/NIYb/zSGG/+0e3f/tHt3/7R7d/+0e3f/tHt3jbR7dz+0e3c/tHt3vTSGG700hhs7NIYbOzSGG4c0hhv/NIYb/zSGG/80hhv/tHt3/7R7d/+0e3f/tHt3/7R7d420e3c/tHt3P7R7d700hhu9NIYbOzSGGzs0hhuHNIYb/zSGG/80hhv/NIYb/7R7d/+0e3f/tHt3/7R7d/+0e3f/tHt3P7R7dz+0e3e9NIYbvTSGGzs0hhs7NIYb/zSGG/80hhv/NIYb/zSGG/+0e3f/tHt3/7R7d/+0e3f/tHt3/7R7d420e3c/tHt3vTSGG700hhs7NIYbhzSGG/80hhv/NIYb/zSGG/80hhv/tHt3/7R7d/+0e3f/tHt3/7R7d/+0e3f/tHt3P7R7d700hhu9NIYbOzSGG/80hhv/NIYb/zSGG/80hhv/NIYb/7R7d420e3f/tHt3/7R7d/+0e3f/tHt3/7R7d420e3e9NIYbvTSGG4c0hhv/NIYb/zSGG/80hhv/NIYb/zSGG4cAAAAAtHt3jbR7d/+0e3f/tHt3/7R7d/+0e3f/tHt3jTSGG4c0hhv/NIYb/zSGG/80hhv/NIYb/zSGG4cAAAAAgAEAAAAAAAAGYAAADnAAAB54AAAeeAAADnAAAA5wAAAOcAAABmAAAAZgAAAGYAAAAkAAAAJAAAAAAAAAgAEAAA==" rel="icon" type="image/x-icon" />
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
        input.query, input.page {
            background: #131313;
            border: 0;
            height: 23px;
            color: white;
            font-size: 15px;
            border-radius: 0px;
            border-bottom: 1px solid #3f3f3f;
            width: 270px;
            padding-left: 5px;
        }
        input.page {
            width: 60px;
            text-align: right;
            padding-right: 5px;
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
            min-width: 1024px;
            width: 100%;
            top: 0;
            position: fixed;
            color: white;
            padding: 10px;
            border-bottom: 1px solid gray;
            z-index: 10;
        }
        .logo {
            font-weight: bold;
            margin-right: 30px;
        }
        .items-count {
            margin-left: 20px;
        }
        #panel {
            right: 20px;
            position: absolute;
            border-radius: 5px;
            background-color: lightgoldenrodyellow;
            padding: 8px;
        }
        .title {
            font-weight: bold;
        }
        #db-list {
            width: 100px;
        }
        #collections {
            width: 110px;
        }
        #items-count {

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
                    <?php foreach (vars('db_list') as $db): ?>
                        <option value="<?= $db ?>" <?= isset($_GET['db']) && $db == $_GET['db'] ? 'selected="selected"':'' ?>><?= $db ?></option>
                    <?php endforeach ?>
                </select>
            </span>
            <span class="collections">
            <?php if (vars('collections')): ?>
                Collection
                <select id="collections">
                    <option></option>
                    <?php foreach (vars('collections') as $collection): ?>
                        <option <?= isset($_GET['collection']) && $_GET['collection'] == $collection ? 'selected':'' ?>>
                            <?= $collection ?>
                        </option>
                    <?php endforeach ?>
                </select>
            <?php endif ?>
            </span>
        <?php if ($pagination = vars('pagination')): ?>
            <span class="pages">
                Page <?= $pagination ?>
            </span>
        <?php endif ?>
    </div>
    <div class="form-error">
        <?php if (vars('collection')): ?>
            <form>
                <?php if (vars('find_error')): ?>
                    <span class="error"><?= vars('find_error') ?></span>
                <?php endif ?>
                <input type="hidden" name="db" value="<?= vars('db') ?>" />
                <input type="hidden" name="collection" value="<?= vars('collection') ?>" />
                <input type="text" id="find" class="query" placeholder='{"userId": 1}' name="find" value='<?= isset($_GET['find']) ? str_replace("'", '"', $_GET['find']):'' ?>' />
            </form>
        <?php endif ?>
    </div>
    <div style="clear: both"></div>
</div>
<div id="container">
    <div id="panel">
        <?php if (vars('item_count')): ?>
            <div id="items-count">
                <b>Items:</b> <?= vars('item_count') ?>
            </div>
        <?php endif ?>
        <?php if (vars('indexes')): ?>
        <div class="title">Indexes</div>
        <?php foreach (vars('indexes') as $index): ?>
            <?php foreach ($index['key'] as $name => $key): ?>
                <?= $name ?><br />
            <?php endforeach ?>
        <?php endforeach ?>
        <?php endif ?>
    </div>
    <div>
        <?php if (vars('find')): ?>
            <?php foreach (vars('find') as $f): ?>
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

        document.getElementById('page').onkeypress = function (e){
            if (!e) e = window.event;
            var keyCode = e.keyCode || e.which;
            if (keyCode == '13'){
                var page = document.getElementById('page').value,
                    d = document.getElementById('db-list'),
                    db = d.options[d.selectedIndex].value,
                    c = document.getElementById('collections'),
                    collection = c.options[c.selectedIndex].value,
                    f = document.getElementById('find').value;

                document.location = '?db=' + db+ '&collection=' + collection + '&page=' + page + '&find=' + f;
            }
        }
    }());
</script>
</body>
</html>
