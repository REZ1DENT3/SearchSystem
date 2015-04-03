<ul>
  <?php foreach($rows as $row) { ?>
    <li><?php echo htmlspecialchars($row['value'],ENT_QUOTES,'UTF-8'); ?></li>
  <?php } ?>
</ul>
