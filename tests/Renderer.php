<?php

use MichalCharvat\A2S\ASCIIToSVG;
use MichalCharvat\A2S\CustomObjects;

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$data = <<<DATA
Shapes :
.----------.     .----------.     .----------.
|[cloud]   |     |[computer]|     |[diamond] |
|          |  +->|          |<-o->|          |
|          |  |  |          |  |  |          |
.----------.  |  .----------.  |  .----------.
--------------o----------------+
.----------.  |  .----------.  |  .----------.
|[document]|  :  |[storage] |  |  |[printer] |
|          |<-+->|          |  |  | Some txt |
|          |     |          |<-+->|          |
.----------.     .----------.     '----------'

.----------.     .----------.     .----------.
|[cl]      |     |[c]       |     |[yn]      |
|          |  +->|          |<-o->|          |
|          |  |  |          |  |  |          |
.----------.  |  .----------.  |  .----------.
--------------o----------------+
.----------.  |  .----------.  |  .----------.
|[d]       |  :  |[st]      |  |  |[p]       |
|          |<-+->|          |  |  | Some txt |
|          |     |          |<-+->|          |
.----------.     .----------.     '----------'


[printer]:  {"a2s:type":"printer","fill":"#ff1493"}
[computer]: {"a2s:type":"computer"}
[cloud]:    {"a2s:type":"cloud"}
[diamond]:  {"a2s:type":"diamond"}
[document]: {"a2s:type":"document"}
[storage]:  {"a2s:type":"storage"}
[p]:        {"a2s:type":"printer","fill":"#ff1493","a2s:delref":true}
[c]:        {"a2s:type":"computer","a2s:delref":true}
[cl]:       {"a2s:type":"cloud","a2s:delref":true}
[yn]:       {"a2s:type":"diamond","a2s:delref":true}
[d]:        {"a2s:type":"document","a2s:delref":true}
[st]:       {"a2s:type":"storage","a2s:delref":true}
DATA;

$a2s = new ASCIIToSVG($data);
CustomObjects::loadObjects();

var_dump(CustomObjects::$objects);
die();

$a2s->setDimensionScale(8, 16);
$a2s->parseGrid();
echo $a2s->render();
