import React from 'react';
import { Card, Button, Table, Badge, Row, Col, ProgressBar } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { FaVoteYea, FaPlus, FaUserTie, FaChartBar } from 'react-icons/fa';

const Elections = () => {
  // Mock elections data
  const elections = [
    { 
      id: 1, 
      title: 'SRC Presidential Election', 
      startDate: '2023-09-10', 
      endDate: '2023-09-15', 
      status: 'Upcoming',
      candidates: 4,
      voterTurnout: 0
    },
    { 
      id: 2, 
      title: 'Faculty Representatives', 
      startDate: '2023-09-12', 
      endDate: '2023-09-17', 
      status: 'Upcoming',
      candidates: 12,
      voterTurnout: 0
    },
    { 
      id: 3, 
      title: 'Student Senate Election', 
      startDate: '2023-08-01', 
      endDate: '2023-08-05', 
      status: 'Active',
      candidates: 8,
      voterTurnout: 45
    },
    { 
      id: 4, 
      title: 'Sports Committee Election', 
      startDate: '2023-07-05', 
      endDate: '2023-07-10', 
      status: 'Completed',
      candidates: 6,
      voterTurnout: 72
    },
    { 
      id: 5, 
      title: 'Cultural Committee Election', 
      startDate: '2023-06-10', 
      endDate: '2023-06-15', 
      status: 'Completed',
      candidates: 5,
      voterTurnout: 65
    }
  ];

  // Active election details (mock data)
  const activeElection = {
    id: 3,
    title: 'Student Senate Election',
    startDate: '2023-08-01',
    endDate: '2023-08-05',
    description: 'Election for student senate representatives from all departments',
    totalVoters: 1500,
    votesSubmitted: 675,
    positions: [
      { title: 'President', candidates: 3, votesCounted: 675 },
      { title: 'Vice President', candidates: 2, votesCounted: 670 },
      { title: 'Secretary', candidates: 3, votesCounted: 668 }
    ]
  };

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>
          <FaVoteYea className="me-2" /> Election Management
        </h2>
        <Button as={Link} to="/dashboard/elections/new" variant="primary">
          <FaPlus className="me-2" /> Setup New Election
        </Button>
      </div>

      {/* Active Election Overview */}
      {activeElection && (
        <Card className="border-0 shadow-sm mb-4">
          <Card.Header className="bg-white">
            <div className="d-flex justify-content-between align-items-center">
              <h5 className="mb-0">Active Election</h5>
              <Button as={Link} to={`/dashboard/elections/${activeElection.id}`} variant="outline-primary" size="sm">
                View Details
              </Button>
            </div>
          </Card.Header>
          <Card.Body>
            <Row>
              <Col md={8}>
                <h4>{activeElection.title}</h4>
                <p className="text-muted">
                  {activeElection.startDate} to {activeElection.endDate}
                </p>
                <p>{activeElection.description}</p>
                <div className="mb-3">
                  <div className="d-flex justify-content-between mb-2">
                    <span>Voter Turnout</span>
                    <span>{Math.round((activeElection.votesSubmitted / activeElection.totalVoters) * 100)}%</span>
                  </div>
                  <ProgressBar 
                    now={Math.round((activeElection.votesSubmitted / activeElection.totalVoters) * 100)} 
                    variant="success" 
                  />
                  <div className="text-muted small mt-1">
                    {activeElection.votesSubmitted} out of {activeElection.totalVoters} registered voters
                  </div>
                </div>
              </Col>
              <Col md={4}>
                <Card className="h-100 bg-light">
                  <Card.Body>
                    <h5 className="mb-3">Positions</h5>
                    <ul className="list-unstyled">
                      {activeElection.positions.map((position, index) => (
                        <li key={index} className="mb-2">
                          <div className="d-flex justify-content-between">
                            <span>{position.title}</span>
                            <span className="badge bg-info">{position.candidates} candidates</span>
                          </div>
                        </li>
                      ))}
                    </ul>
                    <div className="mt-3">
                      <Button variant="success" className="w-100">
                        <FaChartBar className="me-2" /> View Results
                      </Button>
                    </div>
                  </Card.Body>
                </Card>
              </Col>
            </Row>
          </Card.Body>
        </Card>
      )}

      {/* All Elections */}
      <Card className="border-0 shadow-sm">
        <Card.Header className="bg-white">
          <h5 className="mb-0">All Elections</h5>
        </Card.Header>
        <Card.Body className="p-0">
          <Table responsive hover className="mb-0">
            <thead className="bg-light">
              <tr>
                <th>Election Title</th>
                <th>Duration</th>
                <th>Candidates</th>
                <th>Voter Turnout</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {elections.map(election => (
                <tr key={election.id}>
                  <td>{election.title}</td>
                  <td>{election.startDate} to {election.endDate}</td>
                  <td>
                    <Badge bg="info">
                      <FaUserTie className="me-1" /> {election.candidates}
                    </Badge>
                  </td>
                  <td>
                    {election.status === 'Completed' || election.status === 'Active' ? (
                      <div>
                        <ProgressBar 
                          now={election.voterTurnout} 
                          variant={election.voterTurnout > 60 ? 'success' : 'warning'} 
                          style={{ height: '8px' }}
                        />
                        <div className="small mt-1">{election.voterTurnout}%</div>
                      </div>
                    ) : (
                      <span className="text-muted">Not started</span>
                    )}
                  </td>
                  <td>
                    <Badge bg={
                      election.status === 'Upcoming' ? 'warning' : 
                      election.status === 'Active' ? 'success' : 
                      'secondary'
                    }>
                      {election.status}
                    </Badge>
                  </td>
                  <td>
                    <div className="d-flex gap-2">
                      <Button as={Link} to={`/dashboard/elections/${election.id}`} variant="outline-primary" size="sm">
                        Manage
                      </Button>
                      {election.status === 'Completed' && (
                        <Button as={Link} to={`/dashboard/elections/${election.id}/results`} variant="outline-success" size="sm">
                          Results
                        </Button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        </Card.Body>
      </Card>
    </div>
  );
};

export default Elections; 