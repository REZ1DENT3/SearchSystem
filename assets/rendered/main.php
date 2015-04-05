<ul>
  <?php foreach($rows as $row) { ?>
    <li>
      <a <?php echo MtHaml\Runtime::renderAttributes(array(array('href', ($row['url'])), array('target', '__blink')), 'html5', 'UTF-8'); ?>>
        <?php echo htmlspecialchars($row['title'],ENT_QUOTES,'UTF-8'); ?>
      </a>
    </li>
  <?php } ?>
</ul>
<p>
  время выполнения,
  <?php echo htmlspecialchars($time,ENT_QUOTES,'UTF-8'); ?>
  <br>
  выбрано объектов:
  <?php echo htmlspecialchars(count($rows),ENT_QUOTES,'UTF-8'); ?>
</p>
