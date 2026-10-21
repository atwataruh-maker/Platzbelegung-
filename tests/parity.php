<?php
declare(strict_types=1);
require __DIR__.'/../server/Planning.php';require __DIR__.'/../server/Cabins.php';require __DIR__.'/../server/Matches.php';require __DIR__.'/../server/ScoreFont.php';
$input=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);$out=[];
foreach($input as $case){$out[]=match($case['kind']){'pitch'=>\Vfl\Planning::allocate(...$case['args']),'cabins'=>\Vfl\Cabins::allocate(...$case['args']),'matches'=>\Vfl\Matches::parse(...$case['args']),'font'=>\Vfl\ScoreFont::decode(base64_decode($case['args'][0]))};}
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
