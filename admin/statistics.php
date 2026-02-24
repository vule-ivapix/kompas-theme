<?php
/**
 * Kompas – Admin stranica: Statistika objava i fotografija
 *
 * @package Kompas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die( 'Nema pristupa.' );
}

global $wpdb;

$today       = current_time( 'Y-m-d' );
$month_start = current_time( 'Y' ) . '-' . current_time( 'm' ) . '-01';

$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : $month_start;
$date_to   = isset( $_GET['date_to'] )   ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) )   : $today;

if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
	$date_from = $month_start;
}
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
	$date_to = $today;
}

$from_dt = $date_from . ' 00:00:00';
$to_dt   = $date_to   . ' 23:59:59';

// ── Vesti po autoru (+ koliko ima featured image) ────────────
$posts_by_author = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT
			u.ID,
			u.display_name,
			COUNT(p.ID)                                                        AS post_count,
			SUM( CASE WHEN pm.meta_id IS NOT NULL THEN 1 ELSE 0 END )          AS photo_count
		FROM {$wpdb->posts} p
		JOIN {$wpdb->users} u ON p.post_author = u.ID
		LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
		WHERE p.post_status = 'publish'
		  AND p.post_type   = 'post'
		  AND p.post_date  >= %s
		  AND p.post_date  <= %s
		GROUP BY p.post_author
		ORDER BY post_count DESC",
		$from_dt,
		$to_dt
	)
);

// ── Uploadovane slike po autoru ──────────────────────────────
$uploads_by_author = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT
			u.ID,
			u.display_name,
			COUNT(a.ID) AS upload_count
		FROM {$wpdb->posts} a
		JOIN {$wpdb->users} u ON a.post_author = u.ID
		WHERE a.post_type      = 'attachment'
		  AND a.post_mime_type LIKE 'image/%%'
		  AND a.post_date     >= %s
		  AND a.post_date     <= %s
		GROUP BY a.post_author
		ORDER BY upload_count DESC",
		$from_dt,
		$to_dt
	)
);

// Spoji u jedan niz po user ID-u
$uploads_map = array();
foreach ( $uploads_by_author as $row ) {
	$uploads_map[ $row->ID ] = (int) $row->upload_count;
}

// ── Dnevni pregled po autoru ─────────────────────────────────
$daily_rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT
			DATE(p.post_date) AS day,
			u.display_name,
			COUNT(p.ID)       AS cnt
		FROM {$wpdb->posts} p
		JOIN {$wpdb->users} u ON p.post_author = u.ID
		WHERE p.post_status = 'publish'
		  AND p.post_type   = 'post'
		  AND p.post_date  >= %s
		  AND p.post_date  <= %s
		GROUP BY DATE(p.post_date), p.post_author
		ORDER BY day DESC, cnt DESC",
		$from_dt,
		$to_dt
	)
);

// Grupiši dnevne redove po datumu
$daily_by_date = array();
foreach ( $daily_rows as $row ) {
	$daily_by_date[ $row->day ][] = $row;
}

// Totali
$total_posts   = array_sum( array_column( $posts_by_author,  'post_count'   ) );
$total_with_ph = array_sum( array_column( $posts_by_author,  'photo_count'  ) );
$total_uploads = array_sum( array_column( $uploads_by_author,'upload_count' ) );
$active_cnt    = count( $posts_by_author );

// Preseti za datum
$ts = current_time( 'timestamp' );
$presets = array(
	'Danas'        => array( $today, $today ),
	'Ova nedelja'  => array( gmdate( 'Y-m-d', strtotime( 'monday this week', $ts ) ), $today ),
	'Ovaj mesec'   => array( $month_start, $today ),
	'Prošli mesec' => array(
		gmdate( 'Y-m-01', strtotime( 'first day of last month', $ts ) ),
		gmdate( 'Y-m-t',  strtotime( 'first day of last month', $ts ) ),
	),
	'Ova godina'   => array( current_time( 'Y' ) . '-01-01', $today ),
);

$page_url = admin_url( 'admin.php?page=kompas-statistics' );

// Srpski nazivi meseci (isti sistem kao frontend)
$months_sr = array(
	1  => 'januar',  2  => 'februar', 3  => 'mart',
	4  => 'april',   5  => 'maj',     6  => 'jun',
	7  => 'jul',     8  => 'avgust',  9  => 'septembar',
	10 => 'oktobar', 11 => 'novembar', 12 => 'decembar',
);

function kompas_stats_sr_date( $ymd, $months_sr ) {
	$dt = DateTime::createFromFormat( 'Y-m-d', $ymd );
	if ( ! $dt ) return $ymd;
	$d = (int) $dt->format( 'j' );
	$m = $months_sr[ (int) $dt->format( 'n' ) ];
	$y = $dt->format( 'Y' );
	return $d . '. ' . $m . ' ' . $y . '.';
}
?>

<div class="wrap kompas-stats">

	<h1>Kompas &mdash; Statistika</h1>

	<?php /* ── Filter ── */ ?>
	<div class="kompas-stats__filter">
		<form method="get">
			<input type="hidden" name="page" value="kompas-statistics" />
			<div class="kompas-stats__filter-row">
				<label>
					Od:
					<input type="date" name="date_from"
						value="<?php echo esc_attr( $date_from ); ?>"
						max="<?php echo esc_attr( $today ); ?>" />
				</label>
				<label>
					Do:
					<input type="date" name="date_to"
						value="<?php echo esc_attr( $date_to ); ?>"
						max="<?php echo esc_attr( $today ); ?>" />
				</label>
				<button type="submit" class="button button-primary">Prikaži</button>
			</div>
			<div class="kompas-stats__presets">
				<?php foreach ( $presets as $label => $range ) : ?>
				<?php
				$url    = add_query_arg( array( 'date_from' => $range[0], 'date_to' => $range[1] ), $page_url );
				$active = ( $date_from === $range[0] && $date_to === $range[1] ) ? ' button-primary' : ' button-secondary';
				?>
				<a href="<?php echo esc_url( $url ); ?>"
				   class="button<?php echo esc_attr( $active ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
				<?php endforeach; ?>
			</div>
		</form>
	</div>

	<?php /* ── Kartica sa sumama ── */ ?>
	<div class="kompas-stats__cards">
		<div class="kompas-stats__card">
			<span class="kompas-stats__card-num"><?php echo (int) $total_posts; ?></span>
			<span class="kompas-stats__card-label">Objavljenih vesti</span>
		</div>
		<div class="kompas-stats__card">
			<span class="kompas-stats__card-num"><?php echo (int) $total_with_ph; ?></span>
			<span class="kompas-stats__card-label">Vesti sa fotografijom</span>
		</div>
		<div class="kompas-stats__card">
			<span class="kompas-stats__card-num"><?php echo (int) $total_uploads; ?></span>
			<span class="kompas-stats__card-label">Uploadovanih fotografija</span>
		</div>
		<div class="kompas-stats__card">
			<span class="kompas-stats__card-num"><?php echo (int) $active_cnt; ?></span>
			<span class="kompas-stats__card-label">Aktivnih novinara</span>
		</div>
	</div>

	<?php /* ── Dve tabele gore: vesti i fotografije ── */ ?>
	<div class="kompas-stats__cols">

		<div class="kompas-stats__section">
			<h2>Vesti po novinaru</h2>
			<?php if ( empty( $posts_by_author ) ) : ?>
			<p class="kompas-stats__empty">Nema objavljenih vesti u odabranom periodu.</p>
			<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Novinar</th>
						<th class="col-num">Vesti</th>
						<th class="col-num">Sa fotografijom</th>
						<th class="col-num">Uploadovane fotke</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts_by_author as $row ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $row->display_name ); ?></strong></td>
						<td class="col-num">
							<span class="kompas-stats__badge badge--blue">
								<?php echo (int) $row->post_count; ?>
							</span>
						</td>
						<td class="col-num">
							<span class="kompas-stats__badge badge--green">
								<?php echo (int) $row->photo_count; ?>
							</span>
						</td>
						<td class="col-num">
							<span class="kompas-stats__badge badge--orange">
								<?php echo (int) ( isset( $uploads_map[ $row->ID ] ) ? $uploads_map[ $row->ID ] : 0 ); ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<th>Ukupno</th>
						<th class="col-num"><strong><?php echo (int) $total_posts; ?></strong></th>
						<th class="col-num"><strong><?php echo (int) $total_with_ph; ?></strong></th>
						<th class="col-num"><strong><?php echo (int) $total_uploads; ?></strong></th>
					</tr>
				</tfoot>
			</table>
			<?php endif; ?>
		</div>

		<?php /* ── Dnevni pregled ── */ ?>
		<div class="kompas-stats__section">
			<h2>Dnevni pregled</h2>
			<?php if ( empty( $daily_by_date ) ) : ?>
			<p class="kompas-stats__empty">Nema podataka za odabrani period.</p>
			<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:160px">Datum</th>
						<th>Novinar</th>
						<th class="col-num">Vesti</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $daily_by_date as $day => $rows ) : ?>
					<?php
					$day_total = array_sum( array_column( $rows, 'cnt' ) );
					$rowspan   = count( $rows );
					$day_label = kompas_stats_sr_date( $day, $months_sr );
					?>
					<?php foreach ( $rows as $i => $row ) : ?>
					<tr class="<?php echo 0 === $i ? 'day-first' : ''; ?>">
						<?php if ( 0 === $i ) : ?>
						<td rowspan="<?php echo (int) $rowspan; ?>" class="day-cell">
							<strong><?php echo esc_html( $day_label ); ?></strong>
							<br>
							<span class="day-total"><?php echo (int) $day_total; ?> ukupno</span>
						</td>
						<?php endif; ?>
						<td><?php echo esc_html( $row->display_name ); ?></td>
						<td class="col-num">
							<span class="kompas-stats__badge badge--blue">
								<?php echo (int) $row->cnt; ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>

	</div>

</div>

<style>
.kompas-stats { max-width: 1200px; }
.kompas-stats h1 { margin-bottom: 1.5rem; font-size: 1.5rem; }

/* Filter */
.kompas-stats__filter {
	background: #fff;
	border: 1px solid #dcdcde;
	border-radius: 3px;
	padding: 1rem 1.25rem;
	margin-bottom: 1.5rem;
}
.kompas-stats__filter-row {
	display: flex;
	gap: 1rem;
	align-items: center;
	flex-wrap: wrap;
	margin-bottom: 0.75rem;
}
.kompas-stats__filter-row label {
	display: flex;
	align-items: center;
	gap: 0.4rem;
	font-weight: 600;
}
.kompas-stats__filter-row input[type="date"] {
	padding: 4px 8px;
	border: 1px solid #8c8f94;
	border-radius: 3px;
	font-size: 0.875rem;
}
.kompas-stats__presets {
	display: flex;
	gap: 0.4rem;
	flex-wrap: wrap;
}

/* Kartice sa sumama */
.kompas-stats__cards {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 1rem;
	margin-bottom: 1.5rem;
}
.kompas-stats__card {
	background: #fff;
	border: 1px solid #dcdcde;
	border-radius: 3px;
	padding: 1.25rem 1rem;
	text-align: center;
}
.kompas-stats__card-num {
	display: block;
	font-size: 2.5rem;
	font-weight: 700;
	color: #1d2327;
	line-height: 1.1;
}
.kompas-stats__card-label {
	display: block;
	font-size: 0.8125rem;
	color: #646970;
	margin-top: 0.3rem;
}

/* Kolone */
.kompas-stats__cols {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 1.5rem;
	margin-bottom: 2rem;
}
.kompas-stats__section h2 {
	font-size: 0.9375rem;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	color: #1d2327;
	margin-bottom: 0.75rem;
}
.kompas-stats__empty {
	color: #646970;
	font-style: italic;
}

/* Tabela */
.wp-list-table .col-num {
	text-align: center;
	width: 110px;
}
.wp-list-table tfoot th {
	font-size: 0.875rem;
}

/* Badge */
.kompas-stats__badge {
	display: inline-block;
	padding: 2px 10px;
	border-radius: 12px;
	font-weight: 700;
	font-size: 0.875rem;
	min-width: 32px;
	text-align: center;
}
.badge--blue   { background: #dbeafe; color: #1e40af; }
.badge--green  { background: #dcfce7; color: #166534; }
.badge--orange { background: #ffedd5; color: #9a3412; }

/* Dnevni pregled */
.day-cell {
	vertical-align: top;
	border-right: 2px solid #dcdcde !important;
	font-size: 0.875rem;
	line-height: 1.5;
}
.day-total {
	color: #646970;
	font-size: 0.75rem;
}
.day-first td,
.day-first th {
	border-top: 2px solid #c3c4c7 !important;
}

@media ( max-width: 960px ) {
	.kompas-stats__cards { grid-template-columns: repeat(2, 1fr); }
	.kompas-stats__cols  { grid-template-columns: 1fr; }
}
</style>
