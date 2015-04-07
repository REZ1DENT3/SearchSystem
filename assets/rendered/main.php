<!DOCTYPE html>
<html>
  <head>
    <title>Поисковая система</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width-device-width, initial-scale=1">
    <link <?php echo MtHaml\Runtime::renderAttributes(array(array('rel', 'stylesheet'), array('href', ('/' . $web . 'css/bootstrap.min.css'))), 'html5', 'UTF-8'); ?>>
    <link <?php echo MtHaml\Runtime::renderAttributes(array(array('rel', 'stylesheet'), array('href', ('/' . $web . 'css/style.css'))), 'html5', 'UTF-8'); ?>>
  </head>
  <body>
    <div class="container">
      <div class="col-lg-8">
        <div class="row">
          <form method="get" action="/">
            <div id="from_search" class="input-group">
              <div class="input-group-btn">
                <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button">
                  <?php echo htmlspecialchars($select,ENT_QUOTES,'UTF-8'); ?>
                  <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-left" role="menu">
                  <li>
                    <a href="#">All</a>
                  </li>
                  <li>
                    <a href="#">Page</a>
                  </li>
                  <li>
                    <a href="#">Test</a>
                  </li>
                </ul>
              </div>
              <input <?php echo MtHaml\Runtime::renderAttributes(array(array('class', 'form-control'), array('type', 'text'), array('name', 'q'), array('placeholder', 'Поиск..'), array('value', $q)), 'html5', 'UTF-8'); ?>>
              <span class="input-group-btn">
                <button class="btn btn-primary">Найти</button>
              </span>
            </div>
            <input <?php echo MtHaml\Runtime::renderAttributes(array(array('class', 'hidden'), array('name', 'table'), array('value', $select)), 'html5', 'UTF-8'); ?>>
          </form>
          <p></p>
          <ol class="list-group">
            <?php foreach($rows as $key => $row) { ?>
              <li class="list-group-item">
                <span class="badge">
                  <?php echo htmlspecialchars($row['__table'],ENT_QUOTES,'UTF-8'); ?>
                </span>
                <?php if ($row['__table'] == 'page') { ?>
                  <a <?php echo MtHaml\Runtime::renderAttributes(array(array('href', ($row['url'])), array('target', '__blink')), 'html5', 'UTF-8'); ?>>
                    <?php echo htmlspecialchars($row['title'],ENT_QUOTES,'UTF-8'); ?>
                  </a>
                <?php } elseif ($row['__table'] == 'test') { ?>
                  <?php echo htmlspecialchars($row['value'],ENT_QUOTES,'UTF-8'); ?>
                <?php } ?>
              </li>
            <?php } ?>
          </ol>
        </div>
      </div>
      <div class="col-lg-4">
        <?php if (count($rows)) { ?>
          <div class="alert alert-info">
            <?php echo htmlspecialchars("Выбрано объектов: " . count($rows),ENT_QUOTES,'UTF-8'); ?>
          </div>
          <div class="alert alert-success">
            <?php echo htmlspecialchars("Время выполнения запроса: " . round($time, 12),ENT_QUOTES,'UTF-8'); ?>
          </div>
        <?php } ?>
        <div class="alert alert-warning">Автор: Бабичев Максим (REZ1DENT3)</div>
      </div>
    </div>
    <script <?php echo MtHaml\Runtime::renderAttributes(array(array('type', 'text/javascript'), array('src', ('/' . $web . 'js/jquery-1.11.2.min.js'))), 'html5', 'UTF-8'); ?>></script>
    <script <?php echo MtHaml\Runtime::renderAttributes(array(array('type', 'text/javascript'), array('src', ('/' . $web . 'js/bootstrap.min.js'))), 'html5', 'UTF-8'); ?>></script>
    <script <?php echo MtHaml\Runtime::renderAttributes(array(array('type', 'text/javascript'), array('src', ('/' . $web . 'js/select.js'))), 'html5', 'UTF-8'); ?>></script>
  </body>
</html>
