<?php
include('includes/header.php');
if(!isset($_SESSION['login'])){ $model->redirect('login.php');}
$agentOptions = $model->getAgentRoster();
$serviceGroupOptions = $model->getServiceGroups();
$selectedAgent = isset($_POST['agent']) ? $_POST['agent'] : '';
$descriptionValue = isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : '';
$dateStartValue = isset($_POST['date']) ? htmlspecialchars($_POST['date'], ENT_QUOTES, 'UTF-8') : '';
$dateEndValue = isset($_POST['enddate']) ? htmlspecialchars($_POST['enddate'], ENT_QUOTES, 'UTF-8') : '';
$otherPartyValue = isset($_POST['other_party']) ? htmlspecialchars($_POST['other_party'], ENT_QUOTES, 'UTF-8') : '';
$selectedServiceGroup = isset($_POST['service_group']) ? $_POST['service_group'] : '';
$callIdValue = isset($_POST['call_id']) ? htmlspecialchars($_POST['call_id'], ENT_QUOTES, 'UTF-8') : '';
?>
<link rel="stylesheet" href="ui/1.11.2/themes/base/jquery-ui.css">
<script src="jquery-1.10.2.js"></script>
<script src="ui/1.11.2/jquery-ui.js"></script>
<script>
$(function() {
        $('#search-date-start').datepicker({dateFormat:'yy-mm-dd'});
        $('#search-date-end').datepicker({dateFormat:'yy-mm-dd'});

        var $searchForm = $('.search-advanced');
        var $loading = $('#search-loading');
        var $submitButton = $('.search-form__submit');

        $searchForm.on('submit', function() {
                $submitButton.prop('disabled', true).addClass('search-form__submit--loading');
                $loading.attr('aria-hidden', 'false').addClass('search-loading--visible');
        });
});
</script>
<section class="card search-card">
  <div class="search-page__header">
    <div>
      <p class="eyebrow">Advanced search</p>
      <h2 class="search-page__title">Find recordings</h2>
      <p class="search-page__lede">Filter by agent, participants, or time frame to retrieve conversations outside of the recent dashboard view.</p>
    </div>
    <a class="ghost" href="index.php">Return to dashboard</a>
  </div>
  <form action="index.php" method="POST" class="search-advanced">
    <div class="search-advanced__grid">
      <label>
        <span class="eyebrow">Agent</span>
        <select name="agent" id="agent-select">
          <option value="">All agents</option>
          <?php foreach ($agentOptions as $option) {
                    $directoryValue = htmlspecialchars($option['directory'], ENT_QUOTES, 'UTF-8');
                    $labelValue = htmlspecialchars($option['displayName'], ENT_QUOTES, 'UTF-8');
                    $isSelected = ($selectedAgent !== '' && $selectedAgent === $option['directory']) ? ' selected' : '';
          ?>
          <option value="<?php echo $directoryValue; ?>"<?php echo $isSelected; ?>><?php echo $labelValue; ?></option>
          <?php } ?>
        </select>
      </label>
      <label>
        <span class="eyebrow">Description</span>
        <input type="text" name="name" value="<?php echo $descriptionValue; ?>" placeholder="Caller or subject" />
      </label>
      <label>
        <span class="eyebrow">Date start</span>
        <input type="text" name="date" id="search-date-start" value="<?php echo $dateStartValue; ?>" placeholder="YYYY-MM-DD" autocomplete="off" />
      </label>
      <label>
        <span class="eyebrow">Date end</span>
        <input type="text" name="enddate" id="search-date-end" value="<?php echo $dateEndValue; ?>" placeholder="YYYY-MM-DD" autocomplete="off" />
      </label>
      <label>
        <span class="eyebrow">Other party</span>
        <input type="text" name="other_party" value="<?php echo $otherPartyValue; ?>" placeholder="Phone number or contact" />
      </label>
      <label>
        <span class="eyebrow">Service group</span>
        <select name="service_group" id="service-group-select">
          <option value="">All service groups</option>
          <?php foreach ($serviceGroupOptions as $group) {
                    $groupName = isset($group['name']) ? $group['name'] : '';
                    if ($groupName === '') {
                            continue;
                    }
                    $groupNameEsc = htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8');
                    $isSelected = ($selectedServiceGroup !== '' && $selectedServiceGroup === $groupName) ? ' selected' : '';
                    $dataId = isset($group['id']) && $group['id'] !== null ? ' data-id="' . htmlspecialchars((string) $group['id'], ENT_QUOTES, 'UTF-8') . '"' : '';
          ?>
          <option value="<?php echo $groupNameEsc; ?>"<?php echo $isSelected . $dataId; ?>><?php echo $groupNameEsc; ?></option>
          <?php } ?>
        </select>
      </label>
      <label>
        <span class="eyebrow">Call ID</span>
        <input type="text" name="call_id" value="<?php echo $callIdValue; ?>" placeholder="ID" />
      </label>
    </div>
    <div class="search-advanced__footer">
      <div class="search-advanced__hint">Use the dashboard for the latest 14 days of recordings. Searches here retrieve older history and large archives.</div>
      <div class="pill-row">
        <input type="hidden" name="action" value="search">
        <button type="submit" class="primary search-form__submit">Search</button>
        <a class="pill ghost" href="search.php">Clear filters</a>
      </div>
    </div>
    <div class="search-loading" id="search-loading" aria-hidden="true" aria-live="assertive" role="status">
      <div class="search-loading__spinner"></div>
      <div class="search-loading__text">Processing search...</div>
    </div>
  </form>
</section>
<?php include('includes/footer.php'); ?>
