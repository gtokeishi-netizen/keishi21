<?php
/**
 * Grant Card Unified - Clean Simple Edition
 * template-parts/grant-card-unified.php
 * 
 * アーカイブページのサーチセクションデザインに完全統一
 * シンプルで洗練されたカードデザイン
 * 
 * @package Grant_Insight_Clean
 * @version 2.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// 基本データ取得
$post_id = get_the_ID();
if (!$post_id) return;

$title = get_the_title($post_id);
$permalink = get_permalink($post_id);
$excerpt = get_the_excerpt($post_id);

// ACFフィールド取得
$grant_data = [
    'max_amount_numeric' => intval(get_field('max_amount_numeric', $post_id)),
    'max_amount' => get_field('max_amount', $post_id) ?: '',
    'deadline_date' => get_field('deadline_date', $post_id) ?: '',
    'deadline' => get_field('deadline', $post_id) ?: '',
    'application_status' => get_field('application_status', $post_id) ?: 'open',
    'ai_summary' => get_field('ai_summary', $post_id) ?: get_post_meta($post_id, 'ai_summary', true),
    'organization' => get_field('organization', $post_id) ?: '',
];

// タクソノミーデータ
$categories = get_the_terms($post_id, 'grant_category');
$prefectures = get_the_terms($post_id, 'grant_prefecture');
$main_category = ($categories && !is_wp_error($categories)) ? $categories[0]->name : '';

// 地域表示ロジック（47都道府県対応）
$region_display = '全国';
if ($prefectures && !is_wp_error($prefectures)) {
    $prefecture_count = count($prefectures);
    
    if ($prefecture_count >= 47) {
        $region_display = '全国';
    } elseif ($prefecture_count > 3) {
        $region_display = $prefecture_count . '都道府県';
    } elseif ($prefecture_count > 1) {
        $region_names = [];
        foreach (array_slice($prefectures, 0, 2) as $pref) {
            $region_names[] = $pref->name;
        }
        $region_display = implode('・', $region_names);
        if ($prefecture_count > 2) {
            $region_display .= ' 他' . ($prefecture_count - 2) . '件';
        }
    } else {
        $region_display = $prefectures[0]->name;
    }
}

// 金額フォーマット
$amount_display = '要確認';
$max_amount_yen = $grant_data['max_amount_numeric'];
if ($max_amount_yen > 0) {
    if ($max_amount_yen >= 100000000) {
        $amount_display = number_format($max_amount_yen / 100000000, 1) . '億円';
    } elseif ($max_amount_yen >= 10000) {
        $amount_display = number_format($max_amount_yen / 10000) . '万円';
    } else {
        $amount_display = number_format($max_amount_yen) . '円';
    }
} elseif ($grant_data['max_amount']) {
    $amount_display = $grant_data['max_amount'];
}

// ステータス表示
$status_labels = [
    'open' => '募集中',
    'closed' => '募集終了', 
    'planned' => '募集予定',
    'suspended' => '一時停止'
];
$status_display = $status_labels[$grant_data['application_status']] ?? '募集中';
$status_class = 'status-' . $grant_data['application_status'];

// 締切日処理
$deadline_info = null;
if ($grant_data['deadline_date']) {
    $deadline_timestamp = strtotime($grant_data['deadline_date']);
    if ($deadline_timestamp) {
        $current_time = current_time('timestamp');
        $days_remaining = ceil(($deadline_timestamp - $current_time) / (60 * 60 * 24));
        
        if ($days_remaining <= 0) {
            $deadline_info = ['class' => 'expired', 'text' => '募集終了'];
        } elseif ($days_remaining <= 7) {
            $deadline_info = ['class' => 'urgent', 'text' => 'あと' . $days_remaining . '日'];
        } elseif ($days_remaining <= 30) {
            $deadline_info = ['class' => 'warning', 'text' => 'あと' . $days_remaining . '日'];
        } else {
            $deadline_info = ['class' => 'normal', 'text' => date('Y年n月j日', $deadline_timestamp)];
        }
    }
} elseif ($grant_data['deadline']) {
    $deadline_info = ['class' => 'normal', 'text' => $grant_data['deadline']];
}

// CSS読み込み制御
static $styles_loaded = false;
?>

<?php if (!$styles_loaded): $styles_loaded = true; ?>
<style>
/* =============================================================================
   Grant Card - Clean Edition (Archive Unified Design)
   ============================================================================= */

:root {
    /* Clean Archive Color Palette */
    --primary: #2563eb;
    --primary-light: #3b82f6;
    --secondary: #16a34a;
    --accent: #ea580c;
    --danger: #dc2626;
    --warning: #d97706;
    
    /* Grays */
    --white: #ffffff;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    
    /* Spacing */
    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    
    /* Shadows */
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    
    /* Radius */
    --radius-sm: 0.375rem;
    --radius-md: 0.5rem;
    --radius-lg: 0.75rem;
    --radius-xl: 1rem;
    --radius-2xl: 1.5rem;
    
    /* Transition */
    --transition: all 0.2s ease-in-out;
}

/* Card Base */
.grant-card-unified {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    overflow: hidden;
    height: fit-content;
    display: flex;
    flex-direction: column;
}

.grant-card-unified:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    border-color: var(--primary);
}

/* Status Badge */
.grant-status-badge {
    position: absolute;
    top: var(--space-3);
    right: var(--space-3);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-2xl);
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 10;
}

.grant-status-badge.status-open {
    background: var(--secondary);
    color: var(--white);
}

.grant-status-badge.status-closed {
    background: var(--gray-400);
    color: var(--white);
}

.grant-status-badge.status-planned {
    background: var(--warning);
    color: var(--white);
}

/* Card Content */
.grant-card-content {
    padding: var(--space-6);
    flex: 1;
    position: relative;
}

/* Category */
.grant-category-tag {
    display: inline-block;
    padding: var(--space-1) var(--space-3);
    background: var(--gray-100);
    color: var(--gray-700);
    border-radius: var(--radius-2xl);
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: var(--space-3);
}

/* Title */
.grant-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-900);
    line-height: 1.4;
    margin: 0 0 var(--space-3) 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.grant-title a {
    color: inherit;
    text-decoration: none;
    transition: var(--transition);
}

.grant-title a:hover {
    color: var(--primary);
}

/* Summary */
.grant-summary {
    font-size: 0.875rem;
    color: var(--gray-600);
    line-height: 1.5;
    margin-bottom: var(--space-4);
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Info Grid */
.grant-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
    margin-bottom: var(--space-6);
}

.grant-info-item {
    text-align: center;
    padding: var(--space-3);
    background: var(--gray-50);
    border-radius: var(--radius-md);
}

.grant-info-label {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    font-weight: 500;
    margin-bottom: var(--space-1);
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.grant-info-value {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-900);
}

.grant-info-value.amount {
    color: var(--primary);
    font-size: 1rem;
}

.grant-info-value.region {
    color: var(--secondary);
}

/* Deadline Badge */
.grant-deadline {
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-2xl);
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: var(--space-4);
}

.grant-deadline.normal {
    background: var(--gray-100);
    color: var(--gray-700);
}

.grant-deadline.warning {
    background: #fef3c7;
    color: #92400e;
}

.grant-deadline.urgent {
    background: #fee2e2;
    color: #991b1b;
}

.grant-deadline.expired {
    background: var(--gray-200);
    color: var(--gray-600);
}

/* Actions */
.grant-actions {
    display: flex;
    gap: var(--space-2);
    padding: 0 var(--space-6) var(--space-6);
}

.grant-btn {
    flex: 1;
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
}

.grant-btn-primary {
    background: var(--primary);
    color: var(--white);
}

.grant-btn-primary:hover {
    background: var(--primary-light);
    transform: translateY(-1px);
}

.grant-btn-secondary {
    background: var(--white);
    color: var(--primary);
    border: 1px solid var(--gray-300);
}

.grant-btn-secondary:hover {
    border-color: var(--primary);
    background: var(--gray-50);
}

/* Responsive */
@media (max-width: 640px) {
    .grant-card-content {
        padding: var(--space-4);
    }
    
    .grant-info-grid {
        grid-template-columns: 1fr;
        gap: var(--space-2);
    }
    
    .grant-actions {
        flex-direction: column;
        padding: 0 var(--space-4) var(--space-4);
    }
}
</style>
<?php endif; ?>

<!-- Clean Grant Card -->
<article class="grant-card-unified" 
         data-post-id="<?php echo esc_attr($post_id); ?>"
         role="article">
    
    <!-- Status Badge -->
    <div class="grant-status-badge <?php echo esc_attr($status_class); ?>">
        <?php echo esc_html($status_display); ?>
    </div>
    
    <!-- Card Content -->
    <div class="grant-card-content">
        
        <?php if ($main_category): ?>
        <div class="grant-category-tag">
            <?php echo esc_html($main_category); ?>
        </div>
        <?php endif; ?>
        
        <h3 class="grant-title">
            <a href="<?php echo esc_url($permalink); ?>">
                <?php echo esc_html($title); ?>
            </a>
        </h3>
        
        <?php if ($grant_data['ai_summary'] || $excerpt): ?>
        <div class="grant-summary">
            <?php echo esc_html(wp_trim_words($grant_data['ai_summary'] ?: $excerpt, 20, '...')); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($deadline_info): ?>
        <div class="grant-deadline <?php echo esc_attr($deadline_info['class']); ?>">
            📅 <?php echo esc_html($deadline_info['text']); ?>
        </div>
        <?php endif; ?>
        
        <div class="grant-info-grid">
            <div class="grant-info-item">
                <span class="grant-info-label">助成額</span>
                <span class="grant-info-value amount"><?php echo esc_html($amount_display); ?></span>
            </div>
            <div class="grant-info-item">
                <span class="grant-info-label">対象地域</span>
                <span class="grant-info-value region"><?php echo esc_html($region_display); ?></span>
            </div>
        </div>
        
    </div>
    
    <!-- Actions -->
    <div class="grant-actions">
        <a href="<?php echo esc_url($permalink); ?>" class="grant-btn grant-btn-primary">
            👁️ 詳細を見る
        </a>
        <button class="grant-btn grant-btn-secondary" 
                data-post-id="<?php echo esc_attr($post_id); ?>" 
                data-grant-title="<?php echo esc_attr($title); ?>"
                onclick="openGrantAIChat && openGrantAIChat(this)">
            🤖 AI質問
        </button>
    </div>
    
</article>