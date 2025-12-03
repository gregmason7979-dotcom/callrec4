<?php
if(isset($_REQUEST['download']))
{
    $requestedName = isset($_REQUEST['filename']) ? $_REQUEST['filename'] : '';
    $fileName = $requestedName !== '' ? basename($requestedName) : 'recording.mp3';
    $downloadToken = $_REQUEST['download'];

    if (filter_var($downloadToken, FILTER_VALIDATE_URL)) {
        header('Location: ' . $downloadToken);
        exit;
    }

    $basePath = rtrim(maindirectory, '/\\');
    $realBase = realpath($basePath);

    if ($realBase === false) {
        http_response_code(500);
        exit('Recording directory unavailable.');
    }

    $relative = str_replace('\\', '/', $downloadToken);
    $segments = array_filter(explode('/', $relative), 'strlen');
    $targetPath = $basePath;

    foreach ($segments as $segment) {
        if ($segment === '.' || $segment === '..') {
            continue;
        }

        $targetPath .= DIRECTORY_SEPARATOR . $segment;
    }

    $realPath = realpath($targetPath);

    if ($realPath === false || strpos($realPath, $realBase) !== 0 || !is_file($realPath)) {
        http_response_code(404);
        exit('Recording not found.');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary');
    header('Content-disposition: attachment; filename="'.$fileName.'"');
    readfile($realPath);
    exit;
}
?>
<?php include('includes/header.php'); ?>
<?php if(!isset($_SESSION['login'])){
        $model->redirect('login.php');
} ?>
<script src="jquery-1.10.2.js"></script>
<script src="ui/1.11.2/jquery-ui.js"></script>
        <script>
function DHTMLSound(surl,val) {
$('.dummyspan').hide();
$('#dummyspan_'+val).show();
  document.getElementById("dummyspan_"+val+"").innerHTML=
        "<audio controls><source src='"+surl+"' hidden='false' autostart='false' loop='false' ></audio>";

}
$(document).ready(function(){
        var $pageLoader = $('<div class="page-loader" role="status" aria-live="assertive">\n' +
                '  <div class="page-loader__backdrop" aria-hidden="true"></div>\n' +
                '  <div class="page-loader__content">\n' +
                '    <div class="page-loader__spinner" aria-hidden="true"></div>\n' +
                '    <div class="page-loader__text">Loading recordings...</div>\n' +
                '  </div>\n' +
                '</div>');

        $('body').append($pageLoader);

        function setPageLoading(isLoading) {
                if (isLoading) {
                        $pageLoader.addClass('page-loader--active');
                        $('body').attr('aria-busy', 'true');
                } else {
                        $pageLoader.removeClass('page-loader--active');
                        $('body').removeAttr('aria-busy');
                }
        }

        $(document).ajaxStart(function() {
                setPageLoading(true);
        });

        $(document).ajaxStop(function() {
                setPageLoading(false);
        });

        var $syncButton = $('#sync-recordings');
        var $syncStatus = $('#sync-status');

        function setSyncStatus(message, state) {
                if (!$syncStatus.length) {
                        return;
                }

                var stateClasses = 'header-sync__status--success header-sync__status--error header-sync__status--info';
                var appliedClass = state ? 'header-sync__status--' + state : '';

                $syncStatus
                        .removeClass(stateClasses)
                        .addClass(appliedClass)
                        .text(message);
        }

        function formatSyncStats(stats) {
                if (!stats || typeof stats !== 'object') {
                        return '';
                }

                var parts = [];

                if (typeof stats.inserted !== 'undefined') {
                        parts.push(stats.inserted + ' new');
                }

                if (typeof stats.updated !== 'undefined') {
                        parts.push(stats.updated + ' updated');
                }

                if (typeof stats.deleted !== 'undefined') {
                        parts.push(stats.deleted + ' removed');
                }

                if (typeof stats.seen !== 'undefined') {
                        parts.push(stats.seen + ' scanned');
                }

                return parts.length ? ' (' + parts.join(', ') + ')' : '';
        }

        $syncButton.on('click', function(event) {
                event.preventDefault();

                if (!$syncButton.length || $syncButton.prop('disabled')) {
                        return;
                }

                var originalLabel = $syncButton.text();

                setSyncStatus('Syncing recordings...', 'info');

                $.ajax({
                        type: 'POST',
                        url: 'process.php',
                        data: { action: 'sync_index' },
                        dataType: 'json',
                        beforeSend: function() {
                                $syncButton.prop('disabled', true).addClass('header-sync__btn--busy').text('Syncing...');
                        },
                        success: function(response) {
                                if (response && response.success) {
                                        var statsNote = formatSyncStats(response.stats);
                                        var syncedLabel = (response && response.lastSyncedAt) ? response.lastSyncedAt : null;
                                        var syncMessage = 'Database synced with recordings' + statsNote + '.';

                                        if (syncedLabel) {
                                                syncMessage += ' Last synced at ' + syncedLabel + '.';
                                        }

                                        setSyncStatus(syncMessage, 'success');
                                        return;
                                }

                                var errorMessage = (response && response.message) ? response.message : 'Sync failed. Please try again.';
                                setSyncStatus(errorMessage, 'error');
                        },
                        error: function() {
                                setSyncStatus('Unable to sync recordings. Please try again.', 'error');
                        },
                        complete: function() {
                                $syncButton.prop('disabled', false).removeClass('header-sync__btn--busy').text(originalLabel);
                        }
                });
        });

        function getContainers(agentKey) {
                return {
                        detail: $('#detail_'+agentKey),
                        target: $('#show_'+agentKey)
                };
        }

        function fetchRecordings(agentKey, directory, scope, page, shouldScroll) {
                var containers = getContainers(agentKey);
                var $detailRow = containers.detail;
                var $targetContainer = containers.target;
                var scrollAfterLoad = (typeof shouldScroll === 'boolean') ? shouldScroll : false;

                if (!$detailRow.length || !$targetContainer.length) {
                        return;
                }

                $targetContainer
                        .addClass('showfull--visible showfull--loading')
                        .html('<div class="showfull__loading" role="status" aria-live="assertive"><div class="showfull__spinner" aria-hidden="true"></div><div class="showfull__text">Loading recordings...</div></div>');

                $.ajax({
                        type: 'POST',
                        url: 'process.php',
                        data: {
                                action: 'getdirectory',
                                user: agentKey,
                                directory: directory,
                                scope: scope || 'all',
                                page: page || 1
                        },
                        success: function(response) {
                                $targetContainer.html(response).addClass('showfull--visible');
                                $detailRow.addClass('detail-row--visible');

                                if (scrollAfterLoad) {
                                        scrollToRecordingList($detailRow, $targetContainer);
                                }
                        },
                        error: function() {
                                $targetContainer.html('<div class="showfull__error">Unable to load recordings.</div>').addClass('showfull--visible');
                                $detailRow.addClass('detail-row--visible');

                                if (scrollAfterLoad) {
                                        scrollToRecordingList($detailRow, $targetContainer);
                                }
                        },
                        complete: function() {
                                $targetContainer.removeClass('showfull--loading');
                        }
                });
        }

        function scrollToRecordingList($detailRow, $targetContainer) {
                var $scrollTarget = $detailRow.length ? $detailRow : $targetContainer;

                if (!$scrollTarget.length) {
                        return;
                }

                var offsetTop = $scrollTarget.offset().top;

                if (typeof offsetTop !== 'number' || isNaN(offsetTop)) {
                        return;
                }

                $('html, body').stop(true).animate({
                        scrollTop: Math.max(offsetTop - 120, 0)
                }, 300);
        }

        $('.click').on('click', function(event){
                event.preventDefault();

                var $trigger = $(this);
                var agentKey = $trigger.attr('rel');
                var directory = $trigger.attr('subd');

                if (!agentKey || !directory) {
                        return;
                }

                $('.table_row--agent').removeClass('table_row--agent-active');
                $trigger.closest('.table_row').addClass('table_row--agent-active');

                $('.detail-row').removeClass('detail-row--visible');
                $('.showfull').removeClass('showfull--visible').html('');

                fetchRecordings(agentKey, directory, 'all', 1, true);
        });

        $('.app-main').on('click', '.recording-panel__toggle', function(event){
                event.preventDefault();

                var $button = $(this);
                var $panel = $button.closest('.recording-panel');
                var scope = $button.data('scope') || ($panel.length ? $panel.data('scope') : 'recent');
                var agentKey = $button.data('agent') || ($panel.length ? $panel.data('agent') : '');
                var directory = $button.data('directory') || ($panel.length ? $panel.data('directory') : '');

                if (!agentKey || !directory) {
                        return;
                }

                fetchRecordings(agentKey, directory, scope, 1, true);
        });

        $('.app-main').on('click', '.pagination__btn', function(event){
                event.preventDefault();

                var $button = $(this);
                var $panel = $button.closest('.recording-panel');
                var page = parseInt($button.data('page'), 10) || 1;
                var scope = $button.data('scope') || ($panel.length ? $panel.data('scope') : 'recent');
                var agentKey = $button.data('agent') || ($panel.length ? $panel.data('agent') : '');
                var directory = $button.data('directory') || ($panel.length ? $panel.data('directory') : '');

                if (!agentKey || !directory) {
                        return;
                }

                fetchRecordings(agentKey, directory, scope, page, true);
        });
});
        </script>

  	

<?php
        $directory = rtrim(maindirectory, '/') . DIRECTORY_SEPARATOR;
        $selectedAgentFilter = (isset($_POST['agent']) && is_string($_POST['agent'])) ? $_POST['agent'] : '';
        $actionType = (isset($_POST['action']) && is_string($_POST['action'])) ? $_POST['action'] : '';
        $descriptionFilter = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
        $otherPartyFilter = isset($_POST['other_party']) ? trim((string) $_POST['other_party']) : '';
        $serviceGroupFilter = isset($_POST['service_group']) ? trim((string) $_POST['service_group']) : '';
        $callIdFilter = isset($_POST['call_id']) ? trim((string) $_POST['call_id']) : '';

        $startDateFilter = '';
        $endDateFilter = '';

        if (isset($_POST['date']) && $_POST['date'] !== '' && strtotime($_POST['date']) !== false) {
                $startDateFilter = date('Y-m-d', strtotime($_POST['date']));
        }

        if (isset($_POST['enddate']) && $_POST['enddate'] !== '' && strtotime($_POST['enddate']) !== false) {
                $endDateFilter = date('Y-m-d', strtotime($_POST['enddate']));
        }

        $filters = array(
                'description' => $descriptionFilter,
                'other_party' => $otherPartyFilter,
                'service_group' => $serviceGroupFilter,
                'call_id' => $callIdFilter,
                'start_date' => $startDateFilter,
                'end_date' => $endDateFilter,
                'agent' => $selectedAgentFilter,
        );

        $agentRoster = $model->getAgentRoster();
        $agentNameMap = array();

        foreach ($agentRoster as $agentEntry) {
                if (!isset($agentEntry['directory'])) {
                        continue;
                }

                $agentNameMap[$agentEntry['directory']] = isset($agentEntry['displayName']) ? $agentEntry['displayName'] : $agentEntry['directory'];
        }

        function recordingMatchesFilters($filters, $record)
        {
                if (!is_array($record)) {
                        return false;
                }

                $recordDescription = isset($record['description']) ? (string) $record['description'] : '';
                $recordOtherParty = isset($record['other_party']) ? (string) $record['other_party'] : '';
                $recordServiceGroup = isset($record['service_group']) ? (string) $record['service_group'] : '';
                $recordCallId = isset($record['call_id']) ? (string) $record['call_id'] : '';

                $descriptionMatch = ($filters['description'] === '') || stripos($recordDescription, $filters['description']) !== false;
                $otherPartyMatch = ($filters['other_party'] === '') || stripos($recordOtherParty, $filters['other_party']) !== false;
                $serviceGroupMatch = ($filters['service_group'] === '') || stripos($recordServiceGroup, $filters['service_group']) !== false;
                $callIdMatch = ($filters['call_id'] === '') || stripos($recordCallId, $filters['call_id']) !== false;

                $dateMatch = true;

                if ($filters['start_date'] !== '' && $filters['end_date'] !== '') {
                        $recordDate = date('Y-m-d', strtotime($record['datetime']));
                        $dateMatch = (strtotime($recordDate) >= strtotime($filters['start_date'])) && (strtotime($recordDate) <= strtotime($filters['end_date']));
                } elseif ($filters['start_date'] !== '') {
                        $recordDate = date('Y-m-d', strtotime($record['datetime']));
                        $dateMatch = strtotime($recordDate) >= strtotime($filters['start_date']);
                } elseif ($filters['end_date'] !== '') {
                        $recordDate = date('Y-m-d', strtotime($record['datetime']));
                        $dateMatch = strtotime($recordDate) <= strtotime($filters['end_date']);
                }

                return $descriptionMatch && $otherPartyMatch && $serviceGroupMatch && $callIdMatch && $dateMatch;
        }
?>

<section class="hero hero--with-actions">
  <div>
    <p class="eyebrow"><?php echo $recordingSyncLabel !== null ? 'Last synced ' . htmlspecialchars($recordingSyncLabel, ENT_QUOTES, 'UTF-8') : 'Index not synced yet'; ?></p>
    <h1>Find recordings in milliseconds.</h1>
    <p class="lede">Indexed search keeps every agent, call, and tag at your fingertips. Syncs now run incrementally, so you can refresh the index between calls without waiting.</p>
    <div class="hero-actions">
      <button type="button" id="sync-recordings" class="primary" aria-describedby="sync-status">Run smart sync</button>
    </div>
    <p class="hero-status" id="sync-status" role="status">
<?php if ($recordingSyncLabel !== null): ?>
Database synced with recordings. Last synced at <?php echo htmlspecialchars($recordingSyncLabel, ENT_QUOTES, 'UTF-8'); ?>.
<?php else: ?>
No previous sync found. Click to build the index.
<?php endif; ?>
    </p>
  </div>
  <div class="hero-card">
    <div class="hero-card__row">
      <span>Agents</span>
      <strong><?php echo count($agentRoster); ?></strong>
    </div>
    <div class="hero-card__progress">
      <span style="width: 78%"></span>
    </div>
    <div class="hero-card__meta">
      <div>
        <p class="eyebrow">Search mode</p>
        <strong><?php echo $actionType === '' ? 'Browse roster' : 'Filtered results'; ?></strong>
      </div>
      <div>
        <p class="eyebrow">Sync status</p>
        <strong><?php echo $recordingSyncLabel !== null ? 'Up to date' : 'Pending'; ?></strong>
      </div>
    </div>
  </div>
</section>

<section class="card stats stats--solo">
  <div class="card__header">
    <div>
      <p class="eyebrow">Status</p>
      <strong>Workspace health at a glance</strong>
    </div>
    <a class="button-link primary" href="search.php">Open full search</a>
  </div>
  <div class="stats">
    <div class="stat">
      <p class="eyebrow">Agents</p>
      <strong><?php echo count($agentRoster); ?></strong>
      <span class="trend neutral">Active directories</span>
    </div>
    <div class="stat">
      <p class="eyebrow">Sync state</p>
      <strong><?php echo $recordingSyncLabel !== null ? 'Healthy' : 'Pending'; ?></strong>
      <span class="trend <?php echo $recordingSyncLabel !== null ? 'up' : 'neutral'; ?>"><?php echo $recordingSyncLabel !== null ? 'Indexed' : 'Needs sync'; ?></span>
    </div>
    <div class="stat">
      <p class="eyebrow">Viewing</p>
      <strong><?php echo $actionType === '' ? 'All agents' : 'Filtered'; ?></strong>
      <span class="trend neutral">Click an agent to expand</span>
    </div>
    <div class="stat">
      <p class="eyebrow">Workspace</p>
      <strong>Secure</strong>
      <span class="trend up">Session active</span>
    </div>
  </div>
</section>

<section class="card recordings">
  <div class="card__header">
    <div>
      <p class="eyebrow">Agent activity</p>
      <strong><?php echo $actionType === '' ? 'Select an agent to see recordings' : 'Filtered results'; ?></strong>
    </div>
    <div class="pill-row">
      <a class="button-link ghost" href="index.php">Show all agents</a>
    </div>
  </div>
  <div class="content modern-content">
<?php
        if($actionType === '')
        {
        $rosterEntries = $agentRoster;
?>
        <table class="record-table record-table--roster">
          <colgroup>
            <col class="record-col record-col--agent">
            <col class="record-col record-col--other">
            <col class="record-col record-col--datetime">
            <col class="record-col record-col--group">
            <col class="record-col record-col--call">
            <col class="record-col record-col--description">
          </colgroup>
          <thead>
                                           <tr class="table_top">
                                                        <th width="300">Agent Name</th>
                                                <th width="150">Other Parties</th>
                                                <th width="200">Date/Time</th>
                                                        <th>Service Group</th>
                                                        <th>Call ID</th>
                                                        <th>Description</th>
                                           </tr>
          </thead>
          <tbody>
<?php
        if (empty($rosterEntries)) {
?>
        <tr class="table_row table_row--empty">
          <td colspan="6" class="table_cell--empty">
            <div class="empty-roster">
              <p class="empty-roster__title">No agents found.</p>
              <p class="empty-roster__hint">Recordings will appear here once directories are available.</p>
            </div>
          </td>
        </tr>
<?php
        } else {
                foreach ($rosterEntries as $entry) {
                        $agentDomId = htmlspecialchars($entry['domId'], ENT_QUOTES, 'UTF-8');
                        $directoryAttr = htmlspecialchars($entry['directory'], ENT_QUOTES, 'UTF-8');
                        $agentLabelEsc = htmlspecialchars($entry['displayName'], ENT_QUOTES, 'UTF-8');
?>
        <tr class="table_row table_row--agent">
          <td class="table_cell--name">
            <a href="javascript:void(0)" class="click table-link" role="button" rel="<?php echo $agentDomId; ?>" subd="<?php echo $directoryAttr; ?>" data-agent="<?php echo $agentDomId; ?>" data-directory="<?php echo $directoryAttr; ?>" aria-controls="detail_<?php echo $agentDomId; ?>">
              <span class="table_content__primary">
                <span class="icon-chip icon-chip--chevron" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="m10.5 7.5 5 4.5-5 4.5a.75.75 0 0 1-1-.06.75.75 0 0 1 .06-1l3.63-3.27L9.56 8.56a.75.75 0 0 1 1-1.06Z"/></svg></span>
                <span class="icon-chip icon-chip--agent" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="M12 13.25a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 1.5c-3.51 0-6.5 1.92-6.5 4.5a.75.75 0 0 0 .75.75h11.5a.75.75 0 0 0 .75-.75c0-2.58-2.99-4.5-6.5-4.5Z"/></svg></span>
                <span class="table-link__text">
                  <span class="table-link__label"><?php echo $agentLabelEsc; ?></span>
                  <span class="table-link__hint">View recordings</span>
                </span>
              </span>
              <span class="table-link__chevron" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </a>
          </td>
          <td class="table_cell--ghost" aria-hidden="true"></td>
          <td class="table_cell--ghost" aria-hidden="true"></td>
          <td class="table_cell--ghost" aria-hidden="true"></td>
          <td class="table_cell--ghost" aria-hidden="true"></td>
          <td class="table_cell--ghost" aria-hidden="true"></td>
        </tr>
        <tr class="detail-row" id="detail_<?php echo $agentDomId; ?>">
          <td colspan="6">
            <div id="show_<?php echo $agentDomId; ?>" class="showfull" aria-live="polite"></div>
          </td>
        </tr>
<?php
                }
        }
?>
          </tbody>
        </table>
<?php
}else{
                $i=0;
                $indexedResults = $model->searchIndexedRecordings($filters, 500);
                $hasIndexedResults = is_array($indexedResults) && count($indexedResults) > 0;
                $useFilesystemFallback = !is_array($indexedResults) || !$hasIndexedResults;
                $list_full = $useFilesystemFallback ? scandir($directory) : array();

                if (!is_array($list_full)) {
                        $list_full = array();
                }
                $resultsRendered = false;
                ?>
    <table class="record-table">
                                           <tr class="table_top">
                                           <th width="300">Agent Name</th>
                                                <th width="150">Other Parties</th>
                                                <th width="200">Date/Time</th>
                                                        <th>Service Group</th>
                                                        <th>Call ID</th>
                                                        <th>Description</th>
                                           </tr>
        <?php
        if ($hasIndexedResults) {
                $grouped = array();

                foreach ($indexedResults as $record) {
                        $agentKey = isset($record['agent']) ? $record['agent'] : '';
                        $grouped[$agentKey][] = $record;
                }

                foreach ($grouped as $agentKey => $agentRecords) {
                        $agentLabel = isset($agentNameMap[$agentKey]) ? $agentNameMap[$agentKey] : $agentKey;
        ?>
                                              <tr class="table_row table_row--agent"><td colspan="6" class="table_content">
                                                 <span class="icon-chip icon-chip--chevron" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="m10.5 7.5 5 4.5-5 4.5a.75.75 0 0 1-1-.06.75.75 0 0 1 .06-1l3.63-3.27L9.56 8.56a.75.75 0 0 1 1-1.06Z"/></svg></span>
                                                 <span class="icon-chip icon-chip--agent" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="M12 13.25a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 1.5c-3.51 0-6.5 1.92-6.5 4.5a.75.75 0 0 0 .75.75h11.5a.75.75 0 0 0 .75-.75c0-2.58-2.99-4.5-6.5-4.5Z"/></svg></span>
                                                 <span class="table-link">
                                                   <span class="table-link__label"><?php echo htmlspecialchars($agentLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                                   <span class="table-link__hint">Filtered results</span>
                                                 </span>
                                                 <span class="table-link__chevron" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                               </td>
                                          </tr>
        <?php
                        foreach ($agentRecords as $record) {
                                $i++;
                                $resultsRendered = true;
                                echo $model->renderRecordingRow(
                                        $i,
                                        $record['segments'],
                                        $record['downloadName'],
                                        $record['otherparty'],
                                        $record['datetime'],
                                        $record['servicegroup'],
                                        $record['callId'],
                                        $record['description']
                                );
                        }
                }
        }

        if ($useFilesystemFallback) {
                if ($hasIndexedResults === false && is_array($indexedResults)) {
        ?>
        <tr class="table_row table_row--empty"><td colspan="6" class="table_cell--empty">No indexed results were found. Showing filesystem scan instead.</td></tr>
        <?php
                }
        foreach($list_full as $value_full)
        {
                if (in_array($value_full,array(".",".."))) {
                        continue;
                }

                if ($selectedAgentFilter !== '' && $selectedAgentFilter !== $value_full) {
                        continue;
                }

                $agentLabel = isset($agentNameMap[$value_full]) ? $agentNameMap[$value_full] : null;

                if ($agentLabel === null) {
                        $select        =       "select first_name,last_name from dbo.cc_user where id='".ltrim($value_full,'0')."'";
                        $query  =       sqlsrv_query(connect,$select);

                        if($query==true){
                        $result =       sqlsrv_fetch_array($query,SQLSRV_FETCH_ASSOC);
                        $agentLabel = (isset($result['first_name']) ? $result['first_name'] . ' ' : '') . (isset($result['last_name']) ? $result['last_name'] : '');
                        sqlsrv_free_stmt($query);
                        }
                }

                if ($agentLabel === null || $agentLabel === '') {
                        $agentLabel = $value_full;
                }
        ?>
                                               <tr class="table_row table_row--agent"><td colspan="6" class="table_content">
                                                 <span class="icon-chip icon-chip--chevron" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="m10.5 7.5 5 4.5-5 4.5a.75.75 0 0 1-1-.06.75.75 0 0 1 .06-1l3.63-3.27L9.56 8.56a.75.75 0 0 1 1-1.06Z"/></svg></span>
                                                 <span class="icon-chip icon-chip--agent" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="M12 13.25a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 1.5c-3.51 0-6.5 1.92-6.5 4.5a.75.75 0 0 0 .75.75h11.5a.75.75 0 0 0 .75-.75c0-2.58-2.99-4.5-6.5-4.5Z"/></svg></span>
                                                 <span class="table-link">
                                                   <span class="table-link__label"><?php echo htmlspecialchars($agentLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                                   <span class="table-link__hint">Filtered results</span>
                                                 </span>
                                                 <span class="table-link__chevron" aria-hidden="true"><svg viewBox="0 0 24 24" role="presentation"><path fill="currentColor" d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                               </td>
                                          </tr>
                <?php
                        $subdirectory   =       $directory.$value_full;
                        $new_array      =       array();

                        if(is_dir($subdirectory))
                        {
                                $list = $model->Sort_Directory_Files_By_Last_Modified($subdirectory);

                                foreach($list[0] as $value)
                                {
                                        if (in_array($value['file'],array(".",".."))) {
                                                continue;
                                        }

                                        $play   =       $directory.$value_full.DIRECTORY_SEPARATOR.$value['file'];

                                        if(is_dir($play))
                                        {
                                                $unew_array     =       array();
                                                $ulist = $model->Sort_Directory_Files_By_Last_Modified($play);

                                                foreach($ulist[0] as $uval)
                                                {
                                                        if(!is_file($play.DIRECTORY_SEPARATOR.$uval['file'])) {
                                                                continue;
                                                        }

                                                        $uexplode       =       explode('$',$uval['file']);
                                                        $uservicegroup  =       $uexplode[0];
                                                        $udatetime              =       $uexplode[1];
                                                        $udescription   =       $uexplode[3];
                                                        $uotherparty    =       $uexplode[2];
                                                        $ucallid                =       $uexplode[4];
                                                        $ucall                  =       explode('.',$ucallid);
                                                        $recordingMeta = array(
                                                                        'description' => $udescription,
                                                                        'other_party' => $uotherparty,
                                                                        'service_group' => $uservicegroup,
                                                                        'call_id' => $ucall[0],
                                                                        'datetime' => $udatetime
                                                        );

                                                        if (recordingMatchesFilters($filters, $recordingMeta)) {
                                                                $unew_array[]   =       $uval['file'];
                                                        }
                                                }

                                                if(is_array($unew_array))
                                                {
                                                        foreach($unew_array as $uuval)
                                                        {
                                                                $i++;
                                                                $resultsRendered = true;
                                                                $uuplay =       $directory.$value_full.DIRECTORY_SEPARATOR.$value['file'].DIRECTORY_SEPARATOR.$uuval;

                                                                if(!is_file($uuplay)) {
                                                                        continue;
                                                                }

                                                                $uuexplode      =       explode('$',$uuval);
                                                                $uuservicegroup =       $uuexplode[0];
                                                                $uudatetime             =       $uuexplode[1];
                                                                $uudescription  =       $uuexplode[3];
                                                                $uuotherparty   =       $uuexplode[2];
                                                                $uucallid               =       $uuexplode[4];
                                                                $uucall                 =       explode('.',$uucallid);
                                                                ?>
                                                                <?php echo $model->renderRecordingRow(
                                                                        $i,
                                                                        array($value_full, $value['file'], $uuval),
                                                                        $uuval,
                                                                        $uuotherparty,
                                                                        $uudatetime,
                                                                        $uuservicegroup,
                                                                        $uucall[0],
                                                                        $uudescription
                                                                ); ?>
                                                          <?php }
                                                }
                                        }

                                        if(is_file($play))
                                        {
                                                $explode        =       explode('$',$value['file']);
                                                $servicegroup   =       $explode[0];
                                                $datetime               =       $explode[1];
                                                $description    =       $explode[3];
                                                $otherparty             =       $explode[2];
                                                $callid                 =       $explode[4];
                                                $call                   =       explode('.',$callid);
                                                $recordingMeta = array(
                                                                'description' => $description,
                                                                'other_party' => $otherparty,
                                                                'service_group' => $servicegroup,
                                                                'call_id' => $call[0],
                                                                'datetime' => $datetime
                                                );

                                                if (recordingMatchesFilters($filters, $recordingMeta)) {
                                                        $new_array[]    =       $value['file'];
                                                }
                                         }
                                }
                        }

                        if(is_array($new_array))
                        {
                                        foreach($new_array as $val)
                                        {
                                                $i++;
                                                $resultsRendered = true;
                                                $play   =       $directory.$value_full.DIRECTORY_SEPARATOR.$val;
                                                $explode        =       explode('$',$val);
                                                $servicegroup   =       $explode[0];
                                                $datetime               =       $explode[1];
                                                $description    =       $explode[3];
                                        $otherparty             =       $explode[2];
                                        $callid                 =       $explode[4];
                                        $call                   =       explode('.',$callid);
                                        ?>
                                        <?php echo $model->renderRecordingRow(
                                                $i,
                                                array($value_full, $val),
                                                $val,
                                                $otherparty,
                                                $datetime,
                                                $servicegroup,
                                                $call[0],
                                                $description
                                        ); ?>
                                  <?php }
                        }
                }
        }

        if (!$resultsRendered) {
        ?>
        <tr class="table_row table_row--empty"><td colspan="6" class="table_cell--empty">No recordings matched your filters. Try relaxing the criteria.</td></tr>
        <?php
        }
        ?>
                                        </table>

        <?php } ?>
  </div>
</section>

<?php include('includes/footer.php'); ?>
