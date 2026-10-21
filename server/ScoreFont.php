<?php
declare(strict_types=1);
namespace Vfl;
final class ScoreFont {
 public static function decode(string $bytes):array{
  $len=strlen($bytes);if($len>1000000||$len<12)throw new \RuntimeException('Invalid score font');
  $u16=function(int $p)use($bytes,$len):int{if($p<0||$p+2>$len)throw new \RuntimeException('Truncated font');return unpack('n',substr($bytes,$p,2))[1];};
  $u32=function(int $p)use($bytes,$len):int{if($p<0||$p+4>$len)throw new \RuntimeException('Truncated font');return unpack('N',substr($bytes,$p,4))[1];};
  $tables=[];for($i=0;$i<$u16(4);$i++){$p=12+$i*16;$tables[substr($bytes,$p,4)]=$u32($p+8);}
  $post=$tables['post']??0;$cmap=$tables['cmap']??0;if(!$post||!$cmap||$u32($post)!==0x00020000)throw new \RuntimeException('Unsupported font');
  $count=$u16($post+32);$names=[16=>'-'];for($i=0;$i<=9;$i++)$names[19+$i]=(string)$i;
  $custom=[];$p=$post+34+$count*2;while($p<$len&&count($custom)<$count){$n=ord($bytes[$p++]);if($p+$n>$len)break;$custom[]=substr($bytes,$p,$n);$p+=$n;}
  $digits=['zero','one','two','three','four','five','six','seven','eight','nine'];
  $value=function(int $glyph)use($count,$u16,$post,$names,$custom,$digits):?string{if($glyph>=$count)return null;$idx=$u16($post+34+$glyph*2);if($idx<258)return $names[$idx]??null;$name=$custom[$idx-258]??'';if($name==='hyphen')return '-';$n=array_search($name,$digits,true);return $n===false?null:(string)$n;};
  $sub=0;for($i=0;$i<$u16($cmap+2);$i++){$c=$cmap+$u32($cmap+8+$i*8);if($u16($c)===4){$sub=$c;break;}}if(!$sub)throw new \RuntimeException('Unsupported cmap');
  $n=intdiv($u16($sub+6),2);$ends=$sub+14;$starts=$ends+$n*2+2;$deltas=$starts+$n*2;$ranges=$deltas+$n*2;$result=[];
  for($i=0;$i<$n;$i++){$start=$u16($starts+$i*2);$end=$u16($ends+$i*2);$delta=$u16($deltas+$i*2);if($delta>=32768)$delta-=65536;$offset=$u16($ranges+$i*2);
   for($code=$start;$code<=$end&&$code<65535;$code++){$glyph=$offset?$u16($ranges+$i*2+$offset+($code-$start)*2):$code;if(!$offset||$glyph)$glyph=($glyph+$delta)&65535;$digit=$value($glyph);if($digit!==null)$result[mb_chr($code,'UTF-8')]=$digit;}
  }if(!$result)throw new \RuntimeException('Empty font');return $result;
 }
}
