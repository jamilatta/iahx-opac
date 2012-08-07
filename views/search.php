<?php

require_once 'lib/class/dia.class.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->match('/', function (Request $request) use ($app, $DEFAULT_PARAMS, $config) {

    global $lang, $texts;

    $params = array_merge(
        $app['request']->request->all(),
        $app['request']->query->all()
    );

    // if magic quotes gpc is on, this function clean all parameters and
    // results that was modified by the directive
    if (get_magic_quotes_gpc()) {
        $process = array(&$params);
        while (list($key, $val) = each($process)) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);
                if (is_array($v)) {
                    $process[$key][stripslashes($k)] = $v;
                    $process[] = &$process[$key][stripslashes($k)];
                } else {
                    $process[$key][stripslashes($k)] = stripslashes($v);
                }
            }
        }
        unset($process);
    }

    $collectionData = $DEFAULT_PARAMS['defaultCollectionData'];
    $site = $DEFAULT_PARAMS['defaultSite'];
    $col = $DEFAULT_PARAMS['defaultCollection'];

    $fb = "";
    if(isset($params['fb']) and $params['fb'] != "") {
        $fb = $params['fb'];
    }

    $count = $config->documents_per_page;
    if(isset($params['count'])and $params['count'] != "") {
        $count = $params['count'];
    }

    $output = "site";
    if(isset($params['output']) and $params['output'] != "") {
        $output = $params['output'];
    }

    $lang = $DEFAULT_PARAMS['lang'];
    if(isset($params['lang']) and $params['lang'] != "") {
        $lang = $params['lang'];
    }

    $q = "";
    if(isset($params['q']) and $params['q'] != "") {
        $q = $params['q'];
    }
    
    $index = "";
    if(isset($params['index']) and $params['index'] != "") {
        $index = $params['index'];
    }

    $from = 0;
    if(isset($params['from']) and $params['from'] != "") {
        $from = $params['from'];
    }

    $page = 1;
    if(isset($params['page'])and $params['page'] != "") {
        $page = $params['page'];
    }

    $where = "";
    if(isset($params['where'])and $params['where'] != "") {

        $where = $params['where'];
        foreach($collectionData->where_list->where as $item) {
            if($item->name == $where) {
                $where = (string) $item->filter;
                break;
            }
        }
    }

    // TODO
    $sort = "";
    if(isset($params['sort']) and $params['sort'] != "Array") {
        
        $sort = $params['sort'];
        
        $exists = false;
        foreach($collectionData->sort_list->sort as $item) {
            if($sort == $item->name) {
                $exists = true;
                $sort = (string) urlencode($item->value);
                break;
            }
        }

        if(!$exists)            
            $sort = "";
        
    }

    $filter = array();
    if(isset($params['filter']) and $params['filter'] != "Array") {
        $filter = $params['filter'];
    }

    foreach($filter as $key => $value) {
        if($value == "Array" or $value == "Array#" or $value == "") {
            unset($filter[$key]);
        }

        $filter[$key] = str_replace("#", "", $value);
    }

    $where = array($where);
    $filter_search = array_merge($filter, $where);
    
    // Dia response
    $dia = new Dia($site, $col, $count, $output, $lang);
    $dia->setParam('fb', $fb);
    $dia->setParam('sort', $sort);

    $dia_response = $dia->search($q, $index, $filter_search, $from);
    $result = json_decode($dia_response, true);

    // translate
    $texts = parse_ini_file(TRANSLATE_PATH . $lang . "/texts.ini", true);

    // pagination
    $pag = array();
    $pag['total'] = $result['diaServerResponse'][0]['response']['numFound'];
    $pag['total_formatted'] = number_format($pag['total'], 0, ',', '.');
    $pag['start'] = $result['diaServerResponse'][0]['response']['start'];    
    $pag['total_pages'] = (($pag['total']/$count) % 10 == 0) ? (int)($pag['total']/$count) : (int)($pag['total']/$count+1);
    $pag['count'] = $count;
    $range_min = (($page-5) > 0) ? $page-5 : 1;
    $range_max = (($range_min+10) > $pag['total_pages']) ? $pag['total_pages'] : $range_min+10;
    $pag['pages'] = range($range_min, $range_max);

    // HISTORY APP
    $SESSION = $app['session'];
    $SESSION->start();
    if(!$SESSION->has("history")) {
        $SESSION->set('history', array());   
    }
    
    $history = $SESSION->get('history');

    // if doesn't exists a search history with this query, was created
    if($q != "" and !isset($history[$q])) {
        $history[$q]['url'] = $_SERVER['REQUEST_URI'];
        $history[$q]['id'] = md5($q . $_SERVER['REQUEST_URI']);
        $history[$q]['time'] = time();
    } 

    // if exists a search history with this query, but the paramaters are different,
    // update this parameters
    else if(isset($history[$q]) and $history[$q] != $q) {
        $history[$q]['url'] = $_SERVER['REQUEST_URI'];
        $history[$q]['id'] = md5($q . $_SERVER['REQUEST_URI']);
        $history[$q]['time'] = time();    
    }
    
    $SESSION->set('history', $history);   
    $SESSION->save();

    // bookmark
    $SESSION->start();
    $bookmark = $SESSION->get('bookmark');    
    

    // output vars
    $output_array = array();
    $output_array['bookmark'] = $bookmark;
    $output_array['filters'] = $filter;
    $output_array['filters_formatted'] = $filter;
    $output_array['lang'] = $lang;
    $output_array['q'] = $q;
    $output_array['sort'] = $sort;
    $output_array['from'] = $from;
    $output_array['output'] = $output;
    $output_array['collectionData'] = $collectionData;
    $output_array['params'] = $params;
    $output_array['pag'] = $pag;
    $output_array['docs'] = $result['diaServerResponse'][0]['response']['docs'];
    $output_array['clusters'] = $result['diaServerResponse'][0]['facet_counts']['facet_fields'];
    $output_array['config'] = $config;
    $output_array['texts'] = $texts;


    // output
    switch($output) {
        
        case "xml": case "sol":
            return new Response($dia_response, 200, array("Content-type" => "text/xml"));
            break;
            
        case "print":
            return $app['twig']->render('print.html', $output_array); 
            break;

        case "rss":
            $response = new Response($app['twig']->render('export-rss.html', $output_array));
            $response->headers->set('Content-type', 'text/xml');
            return $response->sendHeaders();
            break;

        case "ris":
            $response = new Response($app['twig']->render('export-ris.html', $output_array));
            $response->headers->set('Content-type', 'application/force-download');
            return $response->sendHeaders();
            break;

        case "citation":
            $response = new Response($app['twig']->render('export-citation.html', $output_array));
            $response->headers->set('Content-type', 'application/force-download');
            return $response->sendHeaders();
            break;

        default: 
            return $app['twig']->render('index.html', $output_array);
            break;
    }
    
});

?>