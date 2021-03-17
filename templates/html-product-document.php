<td class="doc-sort">
    <span class="docs-row-actions">
        <span title="Re-order this section" class="docs-btn-reorder ui-sortable-handle">
            <span class="dashicons dashicons-menu"></span>
        </span>
    </span>
</td>
<td colspan="2">
    <input type="text" class="document_title" value="<?php echo isset($each_document) ? $each_document["name"] : ''; ?>" name="document_title[]" placeholder="File name">
</td>
<td colspan="2" class="file_url">
    <input type="text" class="document_url" value="<?php echo isset($each_document) ? $each_document["url"] : ''; ?>" name="document_url[]" placeholder="https://">
</td>
<td>
    <a href="#" class="button upload_doc_button" data-choose="<?php esc_attr_e( 'Choose file', 'woocommerce' ); ?>" data-update="<?php esc_attr_e( 'Insert file URL', 'woocommerce' ); ?>"><?php echo esc_html__( 'Choose file', 'woocommerce' ); ?></a>
</td>
<td class="doc-remove">
    <span class="docs-row-actions">
        <span title="Delete this section" class="remove_doc_button">
            <span class="dashicons dashicons-trash"></span>
        </span>
    </span>
</td>