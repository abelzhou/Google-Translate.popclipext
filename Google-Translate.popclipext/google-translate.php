<?php

function option($key, $deault = '') {
  $tl = getenv($key);
  if (empty($tl)) {
    return $default;
  }
  $start = strpos($tl, '(');
  $end = strpos($tl, ')');
  if ($start === false || $end === false) {
    return $default;
  }
  return substr($tl, $start + 1, $end - $start - 1);
}

function normalize($q) {
  if (method_exists('Normalizer', 'normalize')) {
    return Normalizer::normalize($q);
  }

  $q = str_replace('"', '\\"', $q);
  $command = sprintf('perl -MUnicode::Normalize -Mutf8 -CS -e \'binmode(STDOUT, ":utf8"); print NFC("%s")\'', $q);
  $result = shell_exec($command);
  return $result ? $result : $q;
}

function googleTrans($q, $tl) {
  $query = array(
    "key" => "",
    "format" => "text",
    "target" => $tl,
    "q" => normalize($q),
  );
  $ch = curl_init();
  $url = 'https://translation.googleapis.com/language/translate/v2?';

  curl_setopt($ch, CURLOPT_URL, $url . http_build_query($query));
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Ios-Bundle-Identifier: x',
  ]);
  // curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9090');
  $response = curl_exec($ch);
  curl_close($ch);

  if (!$response) {
    die('');
  }

  return @json_decode($response, true);
}

if ($input = getenv('POPCLIP_TEXT')) {
  $modifier = getenv('POPCLIP_MODIFIER_FLAGS');
  $tlKey = (int)$modifier === 1048576 ? 'POPCLIP_OPTION_TLC' : 'POPCLIP_OPTION_TL';
  $json = googleTrans($input, option($tlKey, 'en'));
  if (!isset($json['data']['translations'][0])) {
    die('');
  }

  echo trim(preg_replace('/\s\s+/', ' ', $json['data']['translations'][0]['translatedText']));
}
?>
