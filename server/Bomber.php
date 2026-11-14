<?php
declare(strict_types=1);
namespace Vfl;
final class Bomber {
 public const SOURCE='https://www.vfl-ramsdorf.de/fussball/stadionzeitung';
 public static function parse(string $html):array{
  $doc=new \DOMDocument();$previous=libxml_use_internal_errors(true);
  try{$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
  $src='';foreach($doc->getElementsByTagName('iframe') as $frame){if($frame->getAttribute('id')==='pdf'){$src=trim($frame->getAttribute('src'));break;}}
  if(str_starts_with($src,'/bomber/'))$src='https://www.vfl-ramsdorf.de'.$src;
  $url=parse_url($src);$path=rawurldecode($url['path']??'');
  if(($url['scheme']??'')!=='https'||($url['host']??'')!=='www.vfl-ramsdorf.de'||isset($url['user'])||isset($url['pass'])||isset($url['port'])||isset($url['query'])||isset($url['fragment'])||!preg_match('~^/bomber/[^/\\\\\x00-\x1f]+\.pdf$~iuD',$path)||str_contains($path,'..'))throw new \RuntimeException('Aktuelle Bomber-Ausgabe nicht gefunden.');
  $label=preg_match('/(\d+)\.Spieltag_(\d{4})/u',$path,$m)?$m[1].'. Spieltag · Saison '.((int)$m[2]-1).'/'.substr($m[2],2):'Vereinszeitung';
  return ['url'=>'https://www.vfl-ramsdorf.de/bomber/'.rawurlencode(basename($path)),'label'=>$label,'source'=>self::SOURCE,'retrievedAt'=>gmdate('c'),'stale'=>false];
 }
 public static function load():array{return Cache::remember('bomber-current',900,604800,fn()=>self::parse(Http::get(self::SOURCE,2000000,12)));}
}
