<?php

/**
 * Bootstrap pager template.
 *
 * Additionally includes First, Prev, Next and Last buttons.
 *
 * How to use it:
 * - If the total number of rows of your list exceeds the page size, include
 *   this file at the bottom of your list/table/page.
 * - $_GET['pager'] contains the current page number, so your list builder must
 *   look into it in order to display the correct items for that page (e.g.
 *   using it to calculate the OFFSET of your SQL query). If not specified,
 *   consider the page number as 1.
 *
 * Required variables:
 * - $total_rows: The number of total rows of your list.
 * - $list_max_size: The page size.
 * - $pager_max_size: The pager size (i.e. how many links should be displayed).
 */

$pager = array();
$curr_page = empty($_GET['pager']) || $_GET['pager'] != intval($_GET['pager']) ? 1 : $_GET['pager'];

$base_path = $_SERVER['REQUEST_URI'];
$url = parse_url($base_path);

if (!empty($url['query'])) {
    parse_str($url['query'], $args);

    if (isset($args['pager'])) {
        // Removing "pager" argument from URL.
        unset($args['pager']);

        // Rebuilding URL.
        $base_path = $url['path'] . '?' . http_build_query($args);
    }
}

$base_path .= '&pager=';
$base_path = htmlspecialchars($base_path);

// Including current page on pager.
$pager[] = array(
    'url' => $base_path . $curr_page,
    'title' => $curr_page,
    'class' => 'active',
);

// Calculating the total number of pages.
$num_pages = (int) ($total_rows / $list_max_size);
if ($total_rows % $list_max_size) {
    $num_pages++;
}

// Calculating the pager size.
$pager_size = $pager_max_size;
if ($num_pages < $pager_size) {
    $pager_size = $num_pages;
}

// Creating queue of items to prepend.
$start = $curr_page - $pager_size > 1 ? $curr_page - $pager_size : 1;
$end = $curr_page - 1;
$queue_prev = $end >= $start ? range($start, $end) : array();

// Creating queue of items to append.
$start = $curr_page + 1;
$end = $curr_page + $pager_size < $num_pages ? $curr_page + $pager_size : $num_pages;
$queue_next = $end >= $start ? range($start, $end) : array();

// Prepending and appending items until we reach the pager size.
$remaining_items = $pager_size - 1;
while ($remaining_items) {
    if (!empty($queue_next)) {
        $page_num = array_shift($queue_next);
        $pager[] = array(
            'title' => $page_num,
            'url' => $base_path . $page_num,
        );

        $remaining_items--;
    }

    if (!$remaining_items) {
        break;
    }

    if (!empty($queue_prev)) {
        $page_num = array_pop($queue_prev);
        array_unshift($pager, array(
            'title' => $page_num,
            'url' => $base_path . $page_num,
        ));

        $remaining_items--;
    }
}

$item = array(
    'title' => '...',
    'class' => 'disabled',
    'url' => '#',
);

if (!empty($queue_prev)) {
    array_unshift($pager, $item);
}

if (!empty($queue_next)) {
    $pager[] = $item;
}

// Adding "First" and "Prev" buttons.
$prefixes = array(
    array(
        'title' => 'First',
        'url' => $base_path . '1',
    ),
    array(
        'title' => 'Prev',
        'url' => $base_path . ($curr_page - 1),
    ),
);

if ($curr_page == 1) {
    foreach (array_keys($prefixes) as $i) {
        $prefixes[$i]['class'] = 'disabled';
        $prefixes[$i]['url'] = '#';
    }
}

// Adding "Next" and "Last" buttons.
$suffixes = array(
    array(
        'title' => 'Next',
        'url' => $base_path . ($curr_page + 1),
    ),
    array(
        'title' => 'Last',
        'url' => $base_path . $num_pages,
    ),
);

if ($curr_page == $num_pages) {
    foreach (array_keys($suffixes) as $i) {
        $suffixes[$i]['class'] = 'disabled';
        $suffixes[$i]['url'] = '#';
    }
}

$pager = array_merge($prefixes, $pager, $suffixes);
?>

<nav aria-label="Page navigation" class="text-center">
    <ul class="pagination">
        <?php foreach ($pager as $page): ?>
            <li class="page-item<?php echo $page['class'] ? ' ' . $page['class'] : ''; ?>">
                <a class="page-link" href="<?php echo $page['url']; ?>"><?php echo $page['title']; ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
