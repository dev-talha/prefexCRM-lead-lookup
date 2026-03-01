<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leadlookup_model extends App_Model
{
    public function find_leads_by_phone($phone)
    {
        $cfg       = (array) get_instance()->config->item('leadlookup');
        $matchMode = (string) ($cfg['phone_match'] ?? 'like');

        $tblLeads  = db_prefix() . 'leads';
        $tblStatus = db_prefix() . 'leads_status';

        $this->db->select('l.id, l.name, l.phonenumber, l.description, l.dateadded, l.lastcontact, s.name AS status_name', false);
        $this->db->from($tblLeads . ' AS l');
        $this->db->join($tblStatus . ' AS s', 's.id = l.status', 'left');

        if ($matchMode === 'exact') {
            $this->db->where('l.phonenumber', $phone);
        } else {
            $this->db->like('l.phonenumber', $phone);
        }

        $this->db->order_by('l.id', 'DESC');
        $rows = $this->db->get()->result_array();

        if (empty($rows)) {
            return [];
        }

        $leadIds = array_map(static function ($r) {
            return (int) $r['id'];
        }, $rows);

        $customFieldsByLead = $this->get_custom_fields_for_leads($leadIds);
        $notesByLead        = $this->get_notes_for_leads($leadIds);
        $activitiesByLead   = $this->get_latest_activities_for_leads($leadIds, 3);

        $out = [];
        foreach ($rows as $r) {
            $leadId = (int) $r['id'];

            $out[] = [
                'id'           => $leadId,
                'name'         => (string) $r['name'],
                'phone'        => (string) $r['phonenumber'],
                'status'       => (string) ($r['status_name'] ?? ''),
                'description'  => (string) ($r['description'] ?? ''),
                'created_date' => (string) ($r['dateadded'] ?? ''),
                'last_contact' => (string) ($r['lastcontact'] ?? ''),

                'custom_fields'     => $customFieldsByLead[$leadId] ?? new stdClass(),
                'notes'             => $notesByLead[$leadId] ?? [],
                'latest_activities' => $activitiesByLead[$leadId] ?? [],
            ];
        }

        return $out;
    }

    private function get_custom_fields_for_leads(array $leadIds)
    {
        if (empty($leadIds)) {
            return [];
        }

        $tblCF  = db_prefix() . 'customfields';
        $tblCFV = db_prefix() . 'customfieldsvalues';

        $this->db->select('cfv.relid AS lead_id, cf.name AS field_name, cfv.value AS field_value', false);
        $this->db->from($tblCFV . ' AS cfv');
        $this->db->join($tblCF . ' AS cf', 'cf.id = cfv.fieldid', 'inner');
        $this->db->where('cf.fieldto', 'leads');
        $this->db->where_in('cfv.relid', $leadIds);

        $rows = $this->db->get()->result_array();

        $byLead = [];
        foreach ($rows as $r) {
            $leadId = (int) $r['lead_id'];
            $name   = (string) $r['field_name'];
            $val    = (string) ($r['field_value'] ?? '');

            if (!isset($byLead[$leadId])) {
                $byLead[$leadId] = [];
            }

            $byLead[$leadId][$name] = $val;
        }

        return $byLead;
    }

    private function get_notes_for_leads(array $leadIds)
    {
        if (empty($leadIds)) {
            return [];
        }

        $tblNotes = db_prefix() . 'notes';
        $tblStaff = db_prefix() . 'staff';

        $this->db->select("
            n.rel_id AS lead_id,
            n.description AS content,
            n.dateadded AS date,
            CONCAT(st.firstname,' ',st.lastname) AS staff_name
        ", false);

        $this->db->from($tblNotes . ' AS n');
        $this->db->join($tblStaff . ' AS st', 'st.staffid = n.addedfrom', 'left');
        $this->db->where('n.rel_type', 'lead');
        $this->db->where_in('n.rel_id', $leadIds);
        $this->db->order_by('n.dateadded', 'DESC');

        $rows = $this->db->get()->result_array();

        $byLead = [];
        foreach ($rows as $r) {
            $leadId = (int) $r['lead_id'];
            if (!isset($byLead[$leadId])) {
                $byLead[$leadId] = [];
            }

            $byLead[$leadId][] = [
                'content'    => (string) ($r['content'] ?? ''),
                'date'       => (string) ($r['date'] ?? ''),
                'staff_name' => $r['staff_name'] !== null ? (string) $r['staff_name'] : null,
            ];
        }

        return $byLead;
    }

    private function get_latest_activities_for_leads(array $leadIds, $limitPerLead = 3)
    {
        if (empty($leadIds)) {
            return [];
        }

        $tblAct   = db_prefix() . 'lead_activity_log';
        $tblStaff = db_prefix() . 'staff';

        if (!$this->db->table_exists($tblAct)) {
            return [];
        }

        $this->db->select("
            a.leadid AS lead_id,
            a.description,
            a.date,
            a.staffid,
            CONCAT(st.firstname,' ',st.lastname) AS staff_name
        ", false);

        $this->db->from($tblAct . ' AS a');
        $this->db->join($tblStaff . ' AS st', 'st.staffid = a.staffid', 'left');
        $this->db->where_in('a.leadid', $leadIds);
        $this->db->order_by('a.date', 'DESC');

        $rows = $this->db->get()->result_array();

        $byLead = [];
        foreach ($rows as $r) {
            $leadId = (int) $r['lead_id'];

            if (!isset($byLead[$leadId])) {
                $byLead[$leadId] = [];
            }

            if (count($byLead[$leadId]) >= (int) $limitPerLead) {
                continue;
            }

            $staffName = null;
            if (!empty($r['staffid']) && $r['staff_name'] !== null) {
                $staffName = (string) $r['staff_name'];
            }

            $byLead[$leadId][] = [
                'description' => (string) ($r['description'] ?? ''),
                'date'        => (string) ($r['date'] ?? ''),
                'staff_name'  => $staffName,
            ];
        }

        return $byLead;
    }
}
