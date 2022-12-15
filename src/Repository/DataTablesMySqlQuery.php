<?php

namespace App\Repository;

/**
 * Utility repo for Mysql backend for DataTables ajax calls.
 */
class DataTablesMySqlQuery
{

    /**
     * Returns array of sql to be executed.
     * public for unit testing.
     */
    public static function getSql($base_sql, $parameters) {

        $start = $parameters['start'];
        $length = $parameters['length'];
        $search = $parameters['search'];
        $orders = $parameters['order'];
        $columns = $parameters['columns'];
        // dump($columns);
        // dump($orders);

        $findColsWith = function($columns, $attr) {
            $cols = array_filter($columns, fn($c) => ($c[$attr] == "true"));
            $cols = array_map(fn($c) => $c['name'], $cols);
            return array_values($cols);
        };

        $named_cols = array_filter($columns, fn($c) => ($c["name"] != ""));
        $select_fields = array_values(
            array_map(fn($c) => $c['name'], $named_cols)
        );
        $orderablecols = $findColsWith($columns, "orderable");
        $orderby = implode(', ', $orderablecols);
        $searchablecols = $findColsWith($columns, "searchable");

        foreach ($orders as $key => $order) {
            $colindex = intval($order['column']);
            $direction = $order['dir'];

            // Apply the sort in for the indicated field, the rest
            // will be sorted ascending.
            $sortfield = $columns[$colindex]['name'];
            $orderby = "ORDER BY {$sortfield} {$direction}, {$orderby}";
        }
        
        $where = '';
        $searchstring = $search['value'];

        if ($searchstring != null)
            $searchstring = trim($searchstring);
        else
            $searchstring = '';

        $searchparts = mb_split("\s+", $searchstring);
        $testlen = function($p) { return mb_strlen($p) > 0; };
        $searchparts = array_filter($searchparts, $testlen);

        $params = [];
        $dosearch = count($searchablecols) > 0 && count($searchparts) > 0;
        
        if ($dosearch) {
            // Note that while "LIKE CONCAT('%', :s{$i}, '%')" loos
            // very odd, it's the only way to get the params to match
            // correctly.  Using '%?%' with an array of [
            // $searchstring ] fails with "invalid parameter number:
            // number of bound variables does not match number of
            // tokens", and using '%:s0%' just doesn't work.

            // If multiple parts, then every part must be contained in
            // at least one field.
            $partwheress = [];
            for ($i = 0; $i < count($searchparts); $i++) {
                $params["s{$i}"] = $searchparts[$i];

                $colwheres = [];
                for ($j = 0; $j < count($searchablecols); $j++) {
                    $cname = $searchablecols[$j];
                    $colwheres[] = "{$cname} LIKE CONCAT('%', :s{$i}, '%')";
                }
                // Part in at least one field.
                $partwheress[] = '(' . implode(' OR ', $colwheres) . ')';
            }
            $where = "WHERE " . implode(' AND ', $partwheress);
        }


        $recordsTotal_sql = "select count(*) from ({$base_sql}) src";
        $recordsFiltered_sql = "select count(*) from ({$base_sql} {$where}) src";
        $select_field_list = implode(', ', $select_fields);
        $data_sql = "SELECT $select_field_list FROM ({$base_sql} {$where} {$orderby} LIMIT $start, $length) src {$orderby}";
        // dump('TOTAL: ' . $recordsTotal_sql);
        // dump("FILTERED:\n\n" . $recordsFiltered_sql);
        // dump("DATA:\n\n" . $data_sql);

        return [
            'recordsTotal' => $recordsTotal_sql,
            'recordsFiltered' => $recordsFiltered_sql,
            'data' => $data_sql,
            'params' => $params
        ];

    }

    /** Returns data for ajax paging. */
    public static function getData($base_sql, $parameters, $conn) {

        $sqla = DataTablesMySqlQuery::getSql($base_sql, $parameters);
        $recordsTotal = $conn->executeQuery($sqla['recordsTotal'])->fetchNumeric()[0];
        $recordsFiltered = $conn->executeQuery($sqla['recordsFiltered'], $sqla['params'])->fetchNumeric()[0];

        $res = $conn->executeQuery($sqla['data'], $sqla['params']);
        $ret = [];
        while (($row = $res->fetchNumeric())) {
            $ret[] = array_values($row);
        }

        $result = [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $ret
        ];
        return $result;
    }
}
