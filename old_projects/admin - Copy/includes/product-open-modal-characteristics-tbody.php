<?php
/**
 * New-product characteristics rows: same structure as product-opening.php (Add flow).
 * Expects: $metals_list, $units, $locations (from auragold_product_add_form_shared_data.php).
 */
if (empty($metals_list) || !is_array($metals_list)) {
    return;
}
$i = 0;
foreach ($metals_list as $metal):
    $char_data = null;
    ?>
                                            <tr>
                                                <td data-col="check"><input type="checkbox" name="row[<?php echo (int) $i; ?>][is_selected]"></td>
                                                <td data-col="metal">
                                                    <div class="pc-metal-cell-inner">
                                                    <span class="pc-row-drag-handle" title="Drag to reorder row"><i class="feather icon-menu"></i></span>
                                                    <input type="hidden" name="row[<?php echo (int) $i; ?>][metal_id]" value="<?php echo (int) ($metal['id'] ?? 0); ?>">
                                                    <input type="hidden" name="row[<?php echo (int) $i; ?>][metal]" value="<?php echo htmlspecialchars($metal['display_name']); ?>">
                                                    <span class="pc-metal-label"><?php echo htmlspecialchars($metal['display_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td data-col="hsn"><input name="row[<?php echo (int) $i; ?>][hsn]" class="form-control form-control-sm" value="<?php echo $metal['hsn_code'] ? htmlspecialchars($metal['hsn_code']) : '7113'; ?>"></td>
                                                <td data-col="unit">
                                                    <select name="row[<?php echo (int) $i; ?>][unit_id]" class="form-control form-control-sm">
                                                        <option value="">Select</option>
                                                        <?php foreach ($units as $u) { ?>
                                                        <option value="<?php echo (int) $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </td>
                                                <td data-col="sku"><input name="row[<?php echo (int) $i; ?>][sku_code]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="making"><input name="row[<?php echo (int) $i; ?>][making_on]" class="form-control form-control-sm" value="Gross Wt"></td>
                                                <td data-col="diamond"><?php
                                                $is_diamond_stones = ($metal['display_name'] == 'Diamond & Stones');
    if ($is_diamond_stones) {
        ?>
                                                    <select name="row[<?php echo (int) $i; ?>][diamond_category]" class="form-control form-control-sm">
                                                        <option value="">Select Diamond Category</option>
                                                        <option value="Diamonds">Diamonds</option>
                                                        <option value="GemStones">GemStones</option>
                                                        <option value="Jewellery" selected>Jewellery</option>
                                                    </select>
                                                <?php } else { ?>
                                                    <input type="text" name="row[<?php echo (int) $i; ?>][diamond_category]" class="form-control form-control-sm" value="">
                                                <?php } ?>
                                                </td>
                                                <td data-col="location">
                                                    <select name="row[<?php echo (int) $i; ?>][location_id]" class="form-control form-control-sm">
                                                        <option value="">Select</option>
                                                        <?php foreach ($locations as $l) { ?>
                                                        <option value="<?php echo (int) $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </td>
                                                <td data-col="carat"><input name="row[<?php echo (int) $i; ?>][carat]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="discount"><input name="row[<?php echo (int) $i; ?>][discount]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="purity-sale"><input name="row[<?php echo (int) $i; ?>][purity_sale]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="purity-purchase"><input type="checkbox" name="row[<?php echo (int) $i; ?>][purity_purchase]" value="1"></td>
                                                <td data-col="wastage-sale"><input name="row[<?php echo (int) $i; ?>][wastage_sale]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="wastage-purchase"><input name="row[<?php echo (int) $i; ?>][wastage_purchase]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="wt-per-piece"><input name="row[<?php echo (int) $i; ?>][wt_per_piece]" class="form-control form-control-sm" value=""></td>
                                                <td class="d-none" data-col="opening-weight"><input name="row[<?php echo (int) $i; ?>][opening_weight]" class="form-control form-control-sm" value=""></td>
                                                <td class="d-none" data-col="opening-purity"><input name="row[<?php echo (int) $i; ?>][opening_purity]" class="form-control form-control-sm" value="<?php echo htmlspecialchars(opening_purity_field_value($metal['display_name'], $char_data)); ?>"></td>
                                                <td class="d-none" data-col="opening-qty"><input name="row[<?php echo (int) $i; ?>][opening_qty]" class="form-control form-control-sm" value=""></td>
                                                <td class="d-none" data-col="opening-finalwt"><input name="row[<?php echo (int) $i; ?>][final_weight]" class="form-control form-control-sm" value="" readonly></td>
                                                <td class="d-none" data-col="opening-rate"><input name="row[<?php echo (int) $i; ?>][rate]" class="form-control form-control-sm" value=""></td>
                                                <td class="d-none" data-col="opening-value"><input name="row[<?php echo (int) $i; ?>][value]" class="form-control form-control-sm" value="" readonly></td>
                                                <td data-col="barcode-digits"><input name="row[<?php echo (int) $i; ?>][barcode_digits]" class="form-control form-control-sm" value="5"></td>
                                                <td data-col="barcode-prefix"><input name="row[<?php echo (int) $i; ?>][barcode_prefix]" class="form-control form-control-sm" value="<?php echo htmlspecialchars(opening_barcode_prefix_value($metal['display_name'], $char_data)); ?>"></td>
                                                <td data-col="barcode"><input name="row[<?php echo (int) $i; ?>][barcode]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="serialized"><input type="checkbox" name="row[<?php echo (int) $i; ?>][serialized_barcode]" value="1"></td>
                                                <td data-col="cut"><input name="row[<?php echo (int) $i; ?>][cut]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="shape"><input name="row[<?php echo (int) $i; ?>][shape]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="color"><input name="row[<?php echo (int) $i; ?>][color]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="clarity"><input name="row[<?php echo (int) $i; ?>][clarity]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="sieve"><input name="row[<?php echo (int) $i; ?>][sieve]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="size"><input name="row[<?php echo (int) $i; ?>][size]" class="form-control form-control-sm" value=""></td>
                                                <td data-col="stylecode"><input name="row[<?php echo (int) $i; ?>][style_code]" class="form-control form-control-sm" value=""></td>
                                            </tr>
    <?php
    $i++;
endforeach;
