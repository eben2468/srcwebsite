import React from 'react';
import { Card, Button, Table, Row, Col, ProgressBar } from 'react-bootstrap';
import { FaMoneyBillWave, FaDownload, FaPlus, FaChartLine, FaFileInvoiceDollar } from 'react-icons/fa';

const Budget = () => {
  // Mock budget data
  const budgetData = {
    totalBudget: 120000,
    allocated: 87500,
    spent: 62300,
    remaining: 57700,
    fiscalYear: '2023/2024'
  };

  // Calculate percentages
  const allocatedPercentage = Math.round((budgetData.allocated / budgetData.totalBudget) * 100);
  const spentPercentage = Math.round((budgetData.spent / budgetData.totalBudget) * 100);
  const remainingPercentage = Math.round((budgetData.remaining / budgetData.totalBudget) * 100);

  // Mock budget categories
  const budgetCategories = [
    { id: 1, name: 'Events & Programs', allocated: 35000, spent: 22500, remaining: 12500 },
    { id: 2, name: 'Administrative', allocated: 20000, spent: 15800, remaining: 4200 },
    { id: 3, name: 'Welfare & Support', allocated: 18000, spent: 12000, remaining: 6000 },
    { id: 4, name: 'Marketing & Communications', allocated: 15000, spent: 9000, remaining: 6000 },
    { id: 5, name: 'Training & Development', allocated: 12000, spent: 3000, remaining: 9000 },
    { id: 6, name: 'Contingency', allocated: 10000, spent: 0, remaining: 10000 },
    { id: 7, name: 'Capital Expenses', allocated: 10000, spent: 0, remaining: 10000 }
  ];

  // Mock recent transactions
  const recentTransactions = [
    { id: 1, date: '2023-07-20', description: 'Cultural Festival Supplies', category: 'Events & Programs', amount: 3500, type: 'Expense' },
    { id: 2, date: '2023-07-15', description: 'Office Supplies', category: 'Administrative', amount: 1200, type: 'Expense' },
    { id: 3, date: '2023-07-10', description: 'Leadership Workshop', category: 'Training & Development', amount: 3000, type: 'Expense' },
    { id: 4, date: '2023-07-05', description: 'Student Support Fund', category: 'Welfare & Support', amount: 5000, type: 'Expense' },
    { id: 5, date: '2023-07-01', description: 'University Funding Allocation', category: 'Income', amount: 30000, type: 'Income' }
  ];

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>
          <FaMoneyBillWave className="me-2" /> Budget Management
        </h2>
        <div className="d-flex gap-2">
          <Button variant="success">
            <FaFileInvoiceDollar className="me-2" /> Record Transaction
          </Button>
          <Button variant="primary">
            <FaDownload className="me-2" /> Export Report
          </Button>
        </div>
      </div>

      {/* Budget Overview */}
      <Row className="mb-4">
        <Col>
          <Card className="border-0 shadow-sm">
            <Card.Header className="bg-white">
              <div className="d-flex justify-content-between align-items-center">
                <h5 className="mb-0">Budget Overview - {budgetData.fiscalYear}</h5>
                <Button variant="outline-primary" size="sm">
                  <FaChartLine className="me-2" /> View Detailed Reports
                </Button>
              </div>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <div className="mb-4">
                    <div className="d-flex justify-content-between mb-2">
                      <h6>Total Budget</h6>
                      <span className="fw-bold">${budgetData.totalBudget.toLocaleString()}</span>
                    </div>
                    <div className="d-flex justify-content-between mb-2">
                      <h6>Allocated</h6>
                      <span>${budgetData.allocated.toLocaleString()} ({allocatedPercentage}%)</span>
                    </div>
                    <ProgressBar 
                      now={allocatedPercentage} 
                      variant="info" 
                      className="mb-3"
                    />
                    <div className="d-flex justify-content-between mb-2">
                      <h6>Spent</h6>
                      <span>${budgetData.spent.toLocaleString()} ({spentPercentage}%)</span>
                    </div>
                    <ProgressBar 
                      now={spentPercentage} 
                      variant="danger" 
                      className="mb-3"
                    />
                    <div className="d-flex justify-content-between mb-2">
                      <h6>Remaining</h6>
                      <span>${budgetData.remaining.toLocaleString()} ({remainingPercentage}%)</span>
                    </div>
                    <ProgressBar 
                      now={remainingPercentage} 
                      variant="success" 
                      className="mb-3"
                    />
                  </div>
                </Col>
                <Col md={6}>
                  <div className="h-100 d-flex flex-column justify-content-center">
                    <div className="text-center mb-3">
                      <div className="display-6 fw-bold text-primary">${budgetData.remaining.toLocaleString()}</div>
                      <div className="text-muted">Available Balance</div>
                    </div>
                    <div className="d-grid gap-2">
                      <Button variant="primary">
                        <FaPlus className="me-2" /> Add Budget Item
                      </Button>
                      <Button variant="outline-secondary">
                        Adjust Budget Allocations
                      </Button>
                    </div>
                  </div>
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Budget Categories */}
      <Row className="mb-4">
        <Col>
          <Card className="border-0 shadow-sm">
            <Card.Header className="bg-white">
              <h5 className="mb-0">Budget Categories</h5>
            </Card.Header>
            <Card.Body className="p-0">
              <Table responsive hover className="mb-0">
                <thead className="bg-light">
                  <tr>
                    <th>Category</th>
                    <th>Allocated</th>
                    <th>Spent</th>
                    <th>Remaining</th>
                    <th>Usage</th>
                  </tr>
                </thead>
                <tbody>
                  {budgetCategories.map(category => {
                    const usagePercentage = Math.round((category.spent / category.allocated) * 100);
                    let variant = 'success';
                    if (usagePercentage > 90) variant = 'danger';
                    else if (usagePercentage > 70) variant = 'warning';

                    return (
                      <tr key={category.id}>
                        <td>{category.name}</td>
                        <td>${category.allocated.toLocaleString()}</td>
                        <td>${category.spent.toLocaleString()}</td>
                        <td>${category.remaining.toLocaleString()}</td>
                        <td style={{ width: '20%' }}>
                          <div className="d-flex align-items-center">
                            <ProgressBar 
                              now={usagePercentage} 
                              variant={variant} 
                              style={{ height: '8px', flexGrow: 1 }}
                            />
                            <span className="ms-2">{usagePercentage}%</span>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </Table>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Recent Transactions */}
      <Row>
        <Col>
          <Card className="border-0 shadow-sm">
            <Card.Header className="bg-white">
              <div className="d-flex justify-content-between align-items-center">
                <h5 className="mb-0">Recent Transactions</h5>
                <Button variant="outline-primary" size="sm">
                  View All Transactions
                </Button>
              </div>
            </Card.Header>
            <Card.Body className="p-0">
              <Table responsive hover className="mb-0">
                <thead className="bg-light">
                  <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Type</th>
                  </tr>
                </thead>
                <tbody>
                  {recentTransactions.map(transaction => (
                    <tr key={transaction.id}>
                      <td>{transaction.date}</td>
                      <td>{transaction.description}</td>
                      <td>{transaction.category}</td>
                      <td>${transaction.amount.toLocaleString()}</td>
                      <td>
                        <span className={`badge bg-${transaction.type === 'Income' ? 'success' : 'danger'}`}>
                          {transaction.type}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default Budget; 