<?php

namespace DataWarehouse\Query\JobEfficiency\GroupBys;

class GroupByPi extends \DataWarehouse\Query\JobEfficiency\GroupBy
{
    public static function getLabel()
    {
        return  'PI';
    }

    public function getInfo()
    {
        return 'The principal investigator of a project.';
    }

    public function __construct()
    {
        parent::__construct(
            'pi',
            array(),
            '
        SELECT  distinct
            gt.person_id as id,
            gt.short_name,
            gt.long_name
        FROM
            piperson gt
        where 1
        order by
            gt.order_id'
        );

        $this->_id_field_name = 'person_id';
        $this->_long_name_field_name = 'long_name';
        $this->_short_name_field_name = 'short_name';
        $this->_order_id_field_name = 'order_id';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->pi_person_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'piperson', 'pip');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->pi_person_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->pi_person_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $name_field = new \DataWarehouse\Query\Model\TableField($this->pi_person_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $shortname_field = new \DataWarehouse\Query\Model\TableField($this->pi_person_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->pi_person_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($shortname_field);

        $query->addGroup($id_field);

        $datatable_pi_person_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'principalinvestigator_person_id');
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $id_field,
            '=',
            $datatable_pi_person_id_field
        ));
        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group, $operation, $whereConstraint)
    {
        $query->addTable($this->pi_person_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->pi_person_table, $this->_id_field_name);
        $datatable_pi_person_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'principalinvestigator_person_id');

        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $id_field,
            '=',
            $datatable_pi_person_id_field
        ));
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $id_field,
                $operation,
                $whereConstraint
            )
        );
    }

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->pi_person_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'principalinvestigator_person_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2($request, "select long_name as field_label from modw.piperson  where person_id in (_filter_) order by order_id");
    }

    public function getPossibleValues($hint = null, $limit = null, $offset = null, array $parameters = array())
    {
        if ($this->_possible_values_query == null) {
            return array();
        }

        $possible_values_query = $this->_possible_values_query;

        foreach ($parameters as $pname => $pvalue) {
            if ($pname == 'person') {
                $possible_values_query = str_ireplace('from ', "from modw.peopleunderpi pup, ", $possible_values_query);
                $possible_values_query = str_ireplace('where ', "where pup.person_id = $pvalue and gt.person_id =  pup.principalinvestigator_person_id and ", $possible_values_query);
            } elseif ($pname == 'provider') {//find the names all the pis that have accounts on the resources at the provider.
                $possible_values_query = str_ireplace('from ', "from modw.systemaccount sa,  modw.resourcefact rf, ", $possible_values_query);
                $possible_values_query = str_ireplace('where ', "where rf.id = sa.resource_id and rf.organization_id = $pvalue and gt.person_id = sa.person_id  and ", $possible_values_query);
            } elseif ($pname == 'institution') {
                $possible_values_query = str_ireplace('where ', "where gt.organization_id = $pvalue  and ", $possible_values_query);
            } elseif ($pname == 'pi') {
                $possible_values_query = str_ireplace('where ', "where gt.person_id = $pvalue  and ", $possible_values_query);
            }
        }

        return parent::getPossibleValues($hint, $limit, $offset, $parameters, $possible_values_query);
    }

    public function getCategory()
    {
        return 'Administrative';
    }
}
