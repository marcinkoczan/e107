<?php
### Auto-generated - DO NOT EDIT ### 
return array (
  'index' => 
  array (
    'config' => 
    array (
      'location' => 'core',
      'format' => 'path',
    ),
    'rules' => 
    array (
    ),
  ),
  'news' => 
  array (
    'config' => 
    array (
      'allowMain' => false,
      'noSingleEntry' => true,
      'legacy' => '{e_BASE}news.php',
      'format' => 'get',
      'selfParse' => true,
      'selfCreate' => true,
      'defaultRoute' => 'list/new',
      'errorRoute' => '',
      'urlSuffix' => '',
      'matchValue' => false,
      'mapVars' => 
      array (
      ),
      'allowVars' => 
      array (
      ),
      'varTemplates' => 
      array (
      ),
      'configPath' => '{e_CORE}url/news/url.php',
      'configClass' => 'core_news_url',
      'location' => 'core',
    ),
    'rules' => 
    array (
    ),
  ),
  'page' => 
  array (
    'config' => 
    array (
      'noSingleEntry' => true,
      'legacy' => '{e_BASE}page.php',
      'format' => 'get',
      'selfParse' => true,
      'selfCreate' => true,
      'defaultRoute' => '',
      'errorRoute' => '',
      'urlSuffix' => '',
      'mapVars' => 
      array (
      ),
      'allowVars' => 
      array (
      ),
      'configPath' => '{e_CORE}url/page/url.php',
      'configClass' => 'core_page_url',
      'location' => 'core',
    ),
    'rules' => 
    array (
    ),
  ),
  'search' => 
  array (
    'config' => 
    array (
      'noSingleEntry' => true,
      'legacy' => '{e_BASE}search.php',
      'format' => 'get',
      'selfParse' => true,
      'selfCreate' => true,
      'defaultRoute' => '',
      'configPath' => '{e_CORE}url/search/url.php',
      'configClass' => 'core_search_url',
      'location' => 'core',
    ),
    'rules' => 
    array (
    ),
  ),
  'system' => 
  array (
    'config' => 
    array (
      'format' => 'get',
      'defaultRoute' => 'error/notfound',
      'configPath' => '{e_CORE}url/system/url.php',
      'configClass' => 'core_system_url',
      'location' => 'core',
    ),
    'rules' => 
    array (
    ),
  ),
  'user' => 
  array (
    'config' => 
    array (
      'noSingleEntry' => true,
      'legacy' => '{e_BASE}user.php',
      'format' => 'get',
      'selfParse' => true,
      'selfCreate' => true,
      'defaultRoute' => '',
      'errorRoute' => '',
      'urlSuffix' => '',
      'mapVars' => 
      array (
      ),
      'allowVars' => 
      array (
      ),
      'configPath' => '{e_CORE}url/user/url.php',
      'configClass' => 'core_user_url',
      'location' => 'core',
    ),
    'rules' => 
    array (
    ),
  ),
);