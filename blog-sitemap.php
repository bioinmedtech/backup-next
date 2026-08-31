<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/blog/VkBlog.php';

$cache = bioinmed_blog_read_cache();
$posts = is_array($cache['posts'] ?? null) ? $cache['posts'] : [];

header('Content-Type: application/xml; charset=UTF-8');

function xml_e($value) {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?php echo xml_e(rtrim(CLINIC_SITE_URL, '/') . '/blog/'); ?></loc>
    <lastmod><?php echo xml_e((string)($cache['updated_at'] ?? date(DATE_ATOM))); ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
  </url>
  <?php foreach ($posts as $post): ?>
    <?php if (is_array($post) && (int)($post['id'] ?? 0) > 0): ?>
      <url>
        <loc><?php echo xml_e(bioinmed_blog_post_url($post, true)); ?></loc>
        <lastmod><?php echo xml_e(date(DATE_ATOM, (int)($post['date'] ?? time()))); ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
      </url>
    <?php endif; ?>
  <?php endforeach; ?>
</urlset>
