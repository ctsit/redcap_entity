<form id="redcap-apb-transactions-filter" class="form-inline">
    <input type="hidden" name="prefix" value="<?php echo $module->PREFIX; ?>">
    <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']); ?>">
    <?php foreach ($select_filters as $key => $filter): ?>
        <div class="form-group">
            <label for="redcap-apb-filter-<?php echo $key; ?>"><?php echo $filter['label']; ?></label>
            <select id="redcap-apb-filter-<?php echo $key; ?>" name="<?php echo $key; ?>" class="form-control">
                <?php $default_value = empty($_GET[$key]) ? '' : $_GET[$key]; ?>
                <option value="">-- Select --</option>
                <?php foreach ($filter['options'] as $value => $option_label): ?>
                    <option value="<?php echo $value; ?>"<?php echo $value == $default_value ? ' selected' : ''; ?>><?php echo $option_label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>
    <?php foreach(array('start_date' => 'Start date', 'end_date' => 'End date') as $field => $label): ?>
        <div class="form-group">
            <label for="redcap-apb-filter-<?php echo $field; ?>"><?php echo $label; ?></label>
            <input type="text"
                   id="redcap-apb-filter-<?php echo $field; ?>"
                   name="<?php echo $field; ?>"
                   class="form-control date-picker"
                   size="10"
                   value="<?php echo empty($_GET[$field]) ? '' : htmlspecialchars($_GET[$field]); ?>">
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>
