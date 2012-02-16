<?php
require_once __DIR__.'/../../.silex/silex.phar';
$app = new Silex\Application();
#$app['debug'] = true;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path'       => __DIR__.'/../views',
  'twig.class_path' => __DIR__.'/../../.silex/vendor/twig/lib',
  'twig.options'    => array('cache' => __DIR__.'/../cache'),
));

$app->register(new Silex\Provider\SessionServiceProvider());

/* ----- Shared Properties ----- */

$app['db'] = $app->share(function () {
  $pdo = new PDO('sqlite:'.__DIR__.'/../db/data.db');
#  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
  return $pdo;
});

$app['uid'] = '%';

$app['login'] = $app->share(function () use ($app) {
  return $app['paswd']['user'] === $app['session']->get('user') ? true : false;
});

$app['param'] = $app->share(function () use ($app) {
  $stm = $app['db']->query('SELECT * FROM param');
  while ($v = $stm->fetch(PDO::FETCH_ASSOC)) $res[$v['key']] = $v['val'];
  return $res;
});

$app['paswd'] = $app->share(function () use ($app) {
  $stm = $app['db']->query('SELECT * FROM paswd');
  while ($v = $stm->fetch(PDO::FETCH_ASSOC)) $res[$v['key']] = $v['val'];
  if (!isset($res['user'])) $res['user'] = 'parcel';
  if (!isset($res['pass'])) $res['pass'] = md5('parcel');
  return $res;
});

$app['twigs'] = $app->share(function () use ($app) {
  $page = $app['pages'];
  $pages = $page($app['uid'], true);
  $stats = 1 > count($pages) ? 404 : 200;
  $param = $app['param'];
  $paswd = $app['paswd'];
  if (isset($param['navigation']))
    foreach (explode(',', $param['navigation']) as $v)
      $navis[] = $page(trim($v));
  return array(
    'debug' => $app['debug'],
    'param' => $param,
    'paswd' => $paswd,
    'pages' => $pages,
    'navis' => $navis?$navis:array(),
    'stats' => $stats);
});

/* ----- Protected Functions ----- */

$app['pages'] = $app->protect(function ($uid = '%', $mkd = false) use ($app) {
  $stm = $app['db']->prepare('SELECT * FROM pages WHERE uid LIKE ? ORDER BY day DESC');
  $stm->execute(array($uid));
  $res = $stm->fetchAll(PDO::FETCH_ASSOC);
  if ($mkd) {
    include_once __DIR__.'/../vendor/php-markdown/markdown.php';
    $map = function ($v) {
      $v['val'] = Markdown($v['val']);
      return $v;
    };
    return array_map($map, $res);
  } else {
    return $res;
  }
});

/* ----- Router ----- */

$app->get('/admin', function (Request $req) use ($app) {
  return $app['login'] ? $app['twig']->render('admin.twig', $app['twigs']) : $app->redirect('/sign/in');
});

$app->get('/{uid}', function ($uid, Request $req) use ($app) {
  $app['uid'] = $uid;
  $pjax = $req->headers->get('X-PJAX');
  return new Response( $app['twig']->render((null == $pjax ? 'view.twig' : 'pjax.twig'), $app['twigs']), $app['twigs']['stats'] );
})->value('uid', '%')->assert('uid', '\d+');

$app->post('/post/{switch}', function ($switch, Request $req) use ($app) {
  if (!$app['login']) {
    $res = array('code' => 401);
  } else {
    $res = array('code' => 201);
    $data = json_decode($req->getContent(), true);
    switch ($switch) {
      case 'paswd' :
        $stm = $app['db']->prepare('INSERT INTO paswd (key,val) VALUES (?, ?)');
        if ('' != $data['user'] && '' != $data['pass']) {
          $app['db']->query('DELETE FROM paswd');
          $stm->execute(array('user', $data['user']));
          $stm->execute(array('pass', md5($data['pass'])));
          $stm = $app['db']->query('SELECT * FROM paswd');
          print_r($stm->fetchAll(PDO::FETCH_ASSOC));
        } else {
          $res = array('code' => 400);
        }
        break;
      case 'param' :
        $app['db']->query('DELETE FROM param');
        $stm = $app['db']->prepare('INSERT INTO param (key, val) VALUES (?, ?)');
        foreach ($data as $key => $val) $stm->execute(array($key, $val));
        break;
      case 'pages' :
        $stm = $app['db']->prepare('INSERT INTO pages (key, val, day) VALUES (?, ?, ?)');
        $stm->execute(array($data['key'], $data['val'], time()));
        break;
      case 'pedit' :
        if ('' == $data['key'] && '' == $data['val']) {
          $stm = $app['db']->prepare('DELETE FROM pages WHERE uid = ?');
          $stm->execute(array($data['uid']));
        } else {
          $stm = $app['db']->prepare('UPDATE pages SET key = ?, val = ? WHERE uid = ?');
          $stm->execute(array($data['key'], $data['val'], $data['uid']));
        }
        break;
      default :
        $res = array('code' => 400);
        break;
    }
  }
  return new Response(json_encode(array_merge($res, array('post' => $data))), $res['code']);
})->value('switch', null);

$app->get('/raw/{uid}', function ($uid) use ($app) {
  $res = $app['pages'];
  return new Response(json_encode($res($uid)), 200);
});

# Authentication
$app->get('/sign/{act}', function ($act, Request $req) use ($app) {
  if ('in' == $act) {
    $user = $req->server->get('PHP_AUTH_USER', false);
    $pass = $req->server->get('PHP_AUTH_PW');
    if ($app['paswd']['user'] === $user && $app['paswd']['pass'] === md5($pass)) {
      $app['session']->set('user', $user);
      return $app->redirect('/admin');
    } else {
      $res = new Response();
      $res->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'site_login'));
      $res->setStatusCode(401, 'Unauthorized');
      return $res;
    }
  } else {
    $app['session']->set('user', null);
    return $app->redirect('/');
  }
})->value('act', 'in');

/*
# Initialize DataBase
$app->get('/init', function () use ($app) {
  $sql = 'CREATE TABLE paswd (key TEXT UNIQUE, val TEXT)';
  $app['db']->query($sql);
  $sql = 'CREATE TABLE pages (uid INTEGER PRIMARY KEY, key TEXT, val TEXT, day INTEGER)';
  $app['db']->query($sql);
  $sql = 'CREATE TABLE param (key TEXT UNIQUE, val TEXT)';
  $app['db']->query($sql);
});
*/
$app->run();
