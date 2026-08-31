<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/blog/VkBlog.php';

$cache = bioinmed_blog_read_cache();
$posts = is_array($cache['posts'] ?? null) ? $cache['posts'] : [];
$siteUrl = rtrim(CLINIC_SITE_URL, '/');
$feedUrl = $siteUrl . '/blog-rss.php';
$blogUrl = $siteUrl . '/blog/';
$updatedAt = (string)($cache['updated_at'] ?? '');
$lastBuildDate = $updatedAt !== '' ? date(DATE_RSS, strtotime($updatedAt) ?: time()) : date(DATE_RSS);

header('Content-Type: application/rss+xml; charset=UTF-8');

function rss_e($value) {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss version="2.0">
  <channel>
    <title><?php echo rss_e(CLINIC_NAME); ?>: блог</title>
    <link><?php echo rss_e($blogUrl); ?></link>
    <description>Публикации клиники БИОИНМЕД</description>
    <language>ru-ru</language>
    <lastBuildDate><?php echo rss_e($lastBuildDate); ?></lastBuildDate>
    <atom:link xmlns:atom="http://www.w3.org/2005/Atom" href="<?php echo rss_e($feedUrl); ?>" rel="self" type="application/rss+xml"/>
    <?php foreach ($posts as $post): ?>
      <?php
      $title = bioinmed_blog_title($post);
      $link = bioinmed_blog_post_url($post, true);
      $vkLink = bioinmed_blog_vk_post_url($post);
      $description = nl2br(rss_e(bioinmed_blog_body_text($post)));
      ?>
      <item>
        <title><?php echo rss_e($title); ?></title>
        <link><?php echo rss_e($link); ?></link>
        <guid isPermaLink="false">vk-wall-<?php echo rss_e((string)($post['owner_id'] ?? -232692344)); ?>-<?php echo rss_e((string)($post['id'] ?? '')); ?></guid>
        <pubDate><?php echo rss_e(date(DATE_RSS, (int)($post['date'] ?? time()))); ?></pubDate>
        <description><![CDATA[<?php echo $description; ?><p><a href="<?php echo rss_e($vkLink); ?>">Открыть в VK</a></p>]]></description>
      </item>
    <?php endforeach; ?>
  </channel>
</rss>
