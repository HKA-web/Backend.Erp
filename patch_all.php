<?php
$files = glob("Modules/*/database/migrations/sql/*.sql");
$tenant_files = glob("Modules/*/database/migrations/Tenant/sql/*.sql");
$all_files = array_merge($files, $tenant_files);

foreach ($all_files as $file) {
    if (strpos($file, 'procedure_reorder_menu') !== false) continue;
    if (strpos($file, 'core.procedures_sequence.sql') !== false) continue; // Skip sequence itself

    $content = file_get_contents($file);
    
    // Check if it's already patched
    if (strpos($content, 'v_final_pk VARCHAR;') !== false) {
        echo "Skipping $file, already patched\n";
        continue;
    }
    
    $parts = explode('CREATE OR REPLACE PROCEDURE ', $content);
    $new_parts = [];
    foreach ($parts as $i => $part) {
        if ($i == 0) {
            $new_parts[] = $part;
            continue;
        }
        
        if (preg_match('/^[^\(]+\.procedure_commit_([a-z_]+)\(/', $part, $m_proc)) {
            $model_lower_proc = $m_proc[1];
            $model_upper_proc = strtoupper(str_replace('_', '', $model_lower_proc));
            
            $part = preg_replace('/(v_new_data\s+JSONB;)/', "$1\n    v_final_pk VARCHAR;", $part);
            
            preg_match('/DELETE FROM [^\s]+ WHERE ([a-z_]+) = v_rec\.master_id;/', $part, $m_pk);
            $pk_name = $m_pk[1] ?? $model_lower_proc . '_id';
            
            $part = preg_replace('/(DELETE FROM [^\s]+ WHERE [a-z_]+ = v_rec\.master_id;\s*)ELSE(\s*(?:--[^\n]*\n\s*)*INSERT INTO)/', "$1    v_final_pk := v_rec.master_id;\n    ELSE\n        v_final_pk := v_rec.$pk_name;\n\n        IF v_old_data IS NULL AND (v_final_pk IS NULL OR v_final_pk = '') THEN\n            v_final_pk := core.get_next_sequence('$model_upper_proc');\n        END IF;\n\n$2", $part);
            
            $part = preg_replace("/VALUES\s*\(\s*v_rec\.$pk_name,/", "VALUES (v_final_pk,", $part);
            
            $part = preg_replace("/WHERE t\.$pk_name = v_rec\.$pk_name;/", "WHERE t.$pk_name = v_final_pk;", $part);
        }
        $new_parts[] = $part;
    }
    
    $new_content = implode('CREATE OR REPLACE PROCEDURE ', $new_parts);
    file_put_contents($file, $new_content);
    echo "Patched $file\n";
}
