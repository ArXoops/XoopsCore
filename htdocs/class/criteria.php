<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * XOOPS Criteria parser for database query
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         GNU GPL 2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @package         class
 * @since           2.0.0
 * @author          Kazumi Ono <onokazu@xoops.org>
 * @author          Nathan Dial
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id$
 */

/**
 * A criteria (grammar?) for a database query.
 *
 * Abstract base class should never be instantiated directly.
 *
 * @abstract
 * @package class
 */
abstract class CriteriaElement
{
    /**
     * Sort order
     *
     * @var string
     */
    protected $order = 'ASC';

    /**
     * @var string
     */
    protected $sort = '';

    /**
     * Number of records to retrieve
     *
     * @var int
     */
    protected $limit = 0;

    /**
     * Offset of first record
     *
     * @var int
     */
    protected $start = 0;

    /**
     * @var string
     */
    protected $groupby = '';


    /**
     * Render the criteria element
     *
     * @return string
     */
    abstract public function render();

    /**
     * Make the criteria into a SQL "WHERE" clause
     *
     * @return string
     */
    abstract public function renderWhere();

    /**
     * Generate an LDAP filter from criteria
     *
     * @return string
     */
    abstract public function renderLdap();

    /**
     * Render as Doctrine QueryBuilder instructions
     *
     * @param QueryBuilder $qb        query builder instance
     * @param string       $whereMode how does this fit in the passed in QueryBuilder?
     *                                '' = as where,'and'= as andWhere, 'or' = as orWhere
     *
     * @return QueryBuilder query builder instance
     */
    abstract public function renderQb(QueryBuilder $qb = null, $whereMode = '');

    /**
     *
     * @param string $sort sort column
     *
     * @return void
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return string sort column
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param string $order sort order ASC or DESC
     *
     * @return void
     */
    public function setOrder($order)
    {
        if ('DESC' == strtoupper($order)) {
            $this->order = 'DESC';
        }
    }

    /**
     * @return string sort order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $limit row limit
     *
     * @return void
     */
    public function setLimit($limit = 0)
    {
        $this->limit = intval($limit);
    }

    /**
     * @return int row limit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $start offset of first row
     *
     * @return void
     */
    public function setStart($start = 0)
    {
        $this->start = intval($start);
    }

    /**
     * @return int start row offset
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param string $group group by
     *
     * @return void
     */
    public function setGroupby($group)
    {
        $this->groupby = $group;
    }

    /**
     * @return string group by
     */
    public function getGroupby()
    {
        return isset($this->groupby) ? $this->groupby : "";
    }
}

/**
 * Collection of multiple {@link CriteriaElement}s
 *
 * @package class
 */
class CriteriaCompo extends CriteriaElement
{
    /**
     * The elements of the collection
     *
     * @var array Array of {@link CriteriaElement} objects
     */
    protected $criteriaElements = array();

    /**
     * Conditions
     *
     * @var array
     */
    protected $conditions = array();

    /**
     * Constructor
     *
     * @param CriteriaElement|null $ele
     * @param string $condition
     */
    public function __construct(CriteriaElement $ele = null, $condition = 'AND')
    {
        if (isset($ele)) {
            $this->add($ele, $condition);
        }
    }

    /**
     * @param CriteriaElement $criteriaElement
     * @param string $condition
     * @return CriteriaCompo
     */
    public function add(CriteriaElement $criteriaElement, $condition = 'AND')
    {
        $this->criteriaElements[] = $criteriaElement;
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Make the criteria into a query string
     *
     * @return string
     */
    public function render()
    {
        $ret = '';
        foreach ($this->criteriaElements as $i => $element) {
            /* @var $element CriteriaElement */
            if ($i == 0) {
                $ret = $element->render();
            } else {
                if (!$render = $element->render()) {
                    continue;
                }
                $ret .= ' ' . $this->conditions[$i] . ' ' . $render;
            }
            $ret = "({$ret})";
        }
        return $ret;
    }

    /**
     * Make the criteria into a SQL "WHERE" clause
     *
     * @return string
     */
    public function renderWhere()
    {
        $ret = $this->render();
        $ret = ($ret != '') ? 'WHERE ' . $ret : $ret;
        return $ret;
    }

    /**
     * Generate an LDAP filter from criteria
     *
     * @return string
     * @author Nathan Dial ndial@trillion21.com
     */
    public function renderLdap()
    {
        $ret = '';
        foreach ($this->criteriaElements as $i => $element) {
            /* @var $element CriteriaElement */
            if ($i == 0) {
                $ret = $element->renderLdap();
            } else {
                $cond = strtoupper($this->conditions[$i]);
                $op = ($cond == "OR") ? "|" : "&";
                $ret = "({$op}{$ret}" . $element->renderLdap() . ")";
            }
        }
        return $ret;
    }

    /**
     * Render as Doctrine QueryBuilder instructions
     *
     * @param QueryBuilder $qb        query builder instance
     * @param string       $whereMode how does this fit in the passed in QueryBuilder?
     *                                '' = as where,'and'= as andWhere, 'or' = as orWhere
     *
     * @return QueryBuilder query builder instance
     */
    public function renderQb(QueryBuilder $qb = null, $whereMode = '')
    {
        if ($qb==null) {
            $qb = Xoops::getInstance()->db()->createXoopsQueryBuilder();
            $whereMode = ''; // first entry in new instance must be where
        }
        $eb = $qb->expr();

        foreach ($this->criteriaElements as $i => $element) {
            if ($i == 0) {
                $qb = $element->renderQb($qb, $whereMode);
            } else {
                $qb = $element->renderQb($qb, $this->conditions[$i]);
            }
        }

        if ($this->limit!=0 || $this->start!=0) {
            $qb->setFirstResult($this->start)
                ->setMaxResults($this->limit);
        }

        if (!empty($this->groupby)) {
            $qb->groupBy($groupby);
        }

        if (!empty($this->sort)) {
            $qb->orderBy($this->sort, $this->order);
        }
        return $qb;
    }
}

/**
 * A single criteria
 *
 * @package class
 */
class Criteria extends CriteriaElement
{
    /**
     * @var string
     */
    public $prefix;

    /**
     * @var string
     */
    public $function;

    /**
     * @var string
     */
    public $column;

    /**
     * @var string
     */
    public $operator;

    /**
     * @var mixed
     */
    public $value;

    /**
     * Constructor
     *
     * @param string $column   column criteria applies to
     * @param string $value    value to compare to column
     * @param string $operator operator to apply to column
     * @param string $prefix   prefix to append to column
     * @param string $function sprintf string taking one string argument applied to column
     */
    public function __construct($column, $value = '', $operator = '=', $prefix = '', $function = '')
    {
        $this->prefix = $prefix;
        $this->function = $function;
        $this->column = $column;
        $this->value = $value;
        $this->operator = $operator;
    }

    /**
     * Make a sql condition string
     *
     * @return string
     */
    public function render()
    {
        $clause = (!empty($this->prefix) ? "{$this->prefix}." : "") . $this->column;
        if (!empty($this->function)) {
            $clause = sprintf($this->function, $clause);
        }
        if (in_array(strtoupper($this->operator), array('IS NULL', 'IS NOT NULL'))) {
            $clause .= ' ' . $this->operator;
        } else {
            if ('' === ($value = trim($this->value))) {
                return '';
            }
            if (!in_array(strtoupper($this->operator), array('IN', 'NOT IN'))) {
                if ((substr($value, 0, 1) != '`') && (substr($value, -1) != '`')) {
                    $value = "'{$value}'";
                } else {
                    if (!preg_match('/^[a-zA-Z0-9_\.\-`]*$/', $value)) {
                        $value = '``';
                    }
                }
            }
            $clause .= " {$this->operator} {$value}";
        }

        return $clause;
    }

    /**
     * Generate an LDAP filter from criteria
     *
     * @return string
     * @author Nathan Dial ndial@trillion21.com, improved by Pierre-Eric MENUET pemen@sourceforge.net
     */
    public function renderLdap()
    {
        $clause = '';
        if ($this->operator == '>') {
            $this->operator = '>=';
        }
        if ($this->operator == '<') {
            $this->operator = '<=';
        }

        if ($this->operator == '!=' || $this->operator == '<>') {
            $operator = '=';
            $clause = "(!(" . $this->column . $operator . $this->value . "))";
        } else {
            if ($this->operator == 'IN') {
                $newvalue = str_replace(array('(', ')'), '', $this->value);
                $tab = explode(',', $newvalue);
                foreach ($tab as $uid) {
                    $clause .= "({$this->column}={$uid})";
                }
                $clause = '(|' . $clause . ')';
            } else {
                $clause = "(" . $this->column . $this->operator . $this->value . ")";
            }
        }
        return $clause;
    }

    /**
     * Make a SQL "WHERE" clause
     *
     * @return string
     */
    public function renderWhere()
    {
        $cond = $this->render();
        return empty($cond) ? '' : "WHERE {$cond}";
    }

    /**
     * Render criteria as Doctrine QueryBuilder instructions
     *
     * @param QueryBuilder $qb        query builder instance
     * @param string       $whereMode how does this fit in the passed in QueryBuilder?
     *                                '' = as where,'and'= as andWhere, 'or' = as orWhere
     *
     * @return QueryBuilder query builder instance
     */
    public function renderQb(QueryBuilder $qb = null, $whereMode = '')
    {
        if ($qb==null) { // initialize querybuilder if not passed in
            $qb = Xoops::getInstance()->db()->createXoopsQueryBuilder();
            $whereMode = ''; // first entry in new instance must be where
        }
        $eb = $qb->expr();

        $column = (empty($this->prefix) ? "" : $this->prefix.'.') . $this->column;

        // this should be done using portability functions
        if (!empty($this->function)) {
            $column = sprintf($this->function, $column);
        }

        $value=trim($this->value);

        $operator = strtolower($this->operator);
        $expr = '';

        // handle special case of value
        if (in_array($operator, array('is null', 'is not null', 'in', 'not in'))) {
            switch ($operator) {
                case 'is null':
                    $expr = $eb->isNull($column);
                    break;
                case 'is not null':
                    $expr = $eb->isNotNull($column);
                    break;
                case 'in':
                    $expr = $column . ' IN ' . $value;
                    break;
                case 'not in':
                    $expr = $column . ' NOT IN '.$value;
                    break;
            }

        } elseif (empty($column)) { // no value is a nop (bug: this should be a valid value)
            $whereMode='none';
            $expr = '2=2';
        } else {
            $colvalue = $qb->createNamedParameter($value);
            switch ($operator) {
                case '=':
                case 'eq':
                    $expr = $eb->eq($column, $colvalue);
                    break;
                case '!=':
                case '<>':
                case 'neq':
                    $expr = $eb->neq($column, $colvalue);
                    break;
                case '<':
                case 'lt':
                    $expr = $eb->lt($column, $colvalue);
                    break;
                case '<=':
                case 'lte':
                    $expr = $eb->lte($column, $colvalue);
                    break;
                case '>':
                case 'gt':
                    $expr = $eb->gt($column, $colvalue);
                    break;
                case '>=':
                case 'gte':
                    $expr = $eb->gte($column, $colvalue);
                    break;
                case 'like':
                    $expr = $eb->like($column, $colvalue);
                    break;
                case 'not like':
                    $expr = $eb->notLike($column, $colvalue);
                    break;
                default:
                    $expr = $eb->comparison($column, strtoupper($operator), $colvalue);
                    break;
            }
        }

        switch (strtolower($whereMode)) {
            case 'and':
                $qb->andWhere($expr);
                break;
            case 'or':
                $qb->orWhere($expr);
                break;
            case '':
//            default:
                $qb->where($expr);
                break;
        }

        if ($this->limit!=0 || $this->start!=0) {
            $qb->setFirstResult($start)
                ->setMaxResults($limit);
        }

        if (!empty($this->groupby)) {
            $qb->groupBy($groupby);
        }

        if (!empty($this->sort)) {
            $qb->orderBy($this->sort, $this->order);
        }

        return $qb;
    }
}
