<?php include_customizes($id, 'before') ?>

<table id="<?php echo $id ?>" width="100%">
<tr><td bgcolor="<?php echo $op_color["core_color_11"] ?>"><font color="<?php echo $op_color["core_color_18"] ?>"><?php echo $form->getAuthMode() ?></font></td></tr>

<tr><td bgcolor="<?php echo $op_color["core_color_4"] ?>">
<form action="<?php echo $link_to ?><?php if ($form->isUtn()) echo '?guid=on' ?>" method="post"<?php if ($form->isUtn()) echo ' utn' ?>>
<?php echo $form ?>
<center>
<input type="submit" value="<?php echo __('Login') ?>">
</center>
</form>

<?php if ($form->getAuthAdapter()->getAuthConfig('invite_mode') == 2 && opToolkit::isEnabledRegistration('mobile')): ?>
<?php echo link_to(__('Registration'), $form->getAuthAdapter()->getAuthConfig('self_invite_action')) ?>
<?php endif; ?>

<?php include_customizes($id, 'bottom') ?>
</td></tr>

</table>
<br>

<?php include_customizes($id, 'after') ?>
