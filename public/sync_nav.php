<?php
function syncNavbar($file, $activeClass) {
    if (!file_exists($file)) return;
    $content = file_get_contents($file);

    // 1. Replace the HTML navbar
    $navReplacement = "<?php\nrequire_once __DIR__ . '/components/PublicNav.php';\nqlpt_render_public_nav(['base' => './', 'active' => '$activeClass']);\n?>";
    $content = preg_replace('/<nav class="home-nav">.*?<\/nav>/s', $navReplacement, $content, 1);

    // 2. Remove the custom JS for navbar and dropdowns to avoid conflicts
    // In index.php, there's toggleDropdown, etc.
    $content = preg_replace('/function toggleDropdown\(event\).*?\}\);/s', '', $content);
    $content = preg_replace('/function togglePublicDropdown\(event\).*?\}\);/s', '', $content);
    $content = preg_replace('/const links = document\.querySelectorAll.*?\}\);/s', '', $content);
    $content = preg_replace('/const menuBtn = document\.getElementById.*?\}\);/s', '', $content);

    // 3. Let's just rely on the new CSS being injected.
    // If we want to be safe, we can strip out the old .home-nav CSS
    // but it's simpler to just let PublicNav's CSS take over.
    // However, index.php has `.nav-item.active` or similar? No, it doesn't.
    // To ensure no underline/background conflict, remove old .nav-item CSS
    $content = preg_replace('/\.nav-item.*?\{.*?\}/s', '', $content);
    $content = preg_replace('/\.nav-links.*?\{.*?\}/s', '', $content);

    file_put_contents($file, $content);
    echo "Synced $file\n";
}

syncNavbar('blog.php', 'blog');
syncNavbar('trogiup.php', 'help');
syncNavbar('index.php', '');

echo "Done.";
