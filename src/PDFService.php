<?php
require_once __DIR__ . '/../vendor/autoload.php';
    
use Dompdf\Dompdf;
use Dompdf\Options;

class PDFService {
    private $dompdf;
    
    public function __construct() {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $this->dompdf = new Dompdf($options);
    }
    
    public function generateLeaderboardPDF($leaderboardData) {
        $html = $this->generateLeaderboardHTML($leaderboardData);
        
        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'landscape');
        $this->dompdf->render();
        
        // Output the PDF
        $filename = 'Aprender_Leaderboard_' . date('Y-m-d_H-i-s') . '.pdf';
        $this->dompdf->stream($filename, [
            'Attachment' => 0 // 0 = inline, 1 = download
        ]);
    }
    
    private function generateLeaderboardHTML($leaderboardData) {
        $currentDate = date('F j, Y');
        $totalPlayers = count($leaderboardData);
        $highestScore = $totalPlayers > 0 ? max(array_column($leaderboardData, 'best_score')) : 0;
        $furthestWave = $totalPlayers > 0 ? max(array_column($leaderboardData, 'best_wave')) : 0;
        $totalGames = $totalPlayers > 0 ? array_sum(array_column($leaderboardData, 'games_played')) : 0;
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Aprender Leaderboard</title>
            <style>
                * {
                    color: #333 !important;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: #f5f5f5 !important;
                    color: #333 !important;
                }
                
                .header {
                    background: #667eea;
                    color: white !important;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                    margin-bottom: 0;
                }
                
                .header h1 {
                    margin: 0;
                    font-size: 2.5rem;
                    font-weight: bold;
                    color: white !important;
                }
                
                .header p {
                    margin: 10px 0 0 0;
                    font-size: 1.2rem;
                    color: white !important;
                }
                
                .stats-container {
                    background: white;
                    padding: 20px;
                    display: flex;
                    justify-content: space-around;
                    border-bottom: 2px solid #e0e0e0;
                }
                
                .stat-item {
                    text-align: center;
                }
                
                .stat-number {
                    font-size: 2rem;
                    font-weight: bold;
                    color: #667eea;
                    margin-bottom: 5px;
                }
                
                .stat-label {
                    color: #666;
                    font-size: 0.9rem;
                }
                
                .leaderboard-container {
                    background: white;
                    border-radius: 0 0 10px 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                
                .table-header {
                    background: #f8f9fa;
                    padding: 15px;
                    border-bottom: 2px solid #e0e0e0;
                }
                
                .table-header h2 {
                    margin: 0;
                    color: #333;
                    font-size: 1.5rem;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 0;
                }
                
                th {
                    background: #667eea;
                    color: white;
                    padding: 15px 10px;
                    text-align: left;
                    font-weight: bold;
                    font-size: 0.9rem;
                }
                
                td {
                    padding: 12px 10px;
                    border-bottom: 1px solid #e0e0e0;
                    font-size: 0.85rem;
                }
                
                tr:nth-child(even) {
                    background: #f9f9f9;
                }
                
                tr:hover {
                    background: #f0f0f0;
                }
                
                .rank {
                    text-align: center;
                    font-weight: bold;
                    font-size: 1rem;
                    width: 50px;
                }
                
                .rank-1 { color: #fbbf24; }
                .rank-2 { color: #9ca3af; }
                .rank-3 { color: #f59e0b; }
                
                .player-name {
                    font-weight: bold;
                    color: #333;
                }
                
                .score {
                    font-weight: bold;
                    color: #059669;
                    text-align: right;
                }
                
                .wave {
                    color: #7c3aed;
                    font-weight: bold;
                    text-align: center;
                }
                
                .games-count {
                    text-align: center;
                    color: #666;
                }
                
                .last-game {
                    color: #666;
                    font-size: 0.8rem;
                    text-align: center;
                }
                
                .footer {
                    text-align: center;
                    padding: 20px;
                    color: #666;
                    font-size: 0.8rem;
                    background: #f8f9fa;
                    border-top: 1px solid #e0e0e0;
                }
                
                .no-data {
                    text-align: center;
                    padding: 40px;
                    color: #666;
                }
                
                .no-data h3 {
                    color: #333;
                    margin-bottom: 10px;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🏆 Aprender Leaderboard</h1>
                <p>Gamified Problem-Based Learning System</p>
                <p>Generated on " . $currentDate . "</p>
            </div>
            
            <div class='stats-container'>
                <div class='stat-item'>
                    <div class='stat-number'>" . $totalPlayers . "</div>
                    <div class='stat-label'>Active Players</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>" . number_format($highestScore) . "</div>
                    <div class='stat-label'>Highest Score</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>" . $furthestWave . "</div>
                    <div class='stat-label'>Furthest Wave</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>" . number_format($totalGames) . "</div>
                    <div class='stat-label'>Total Games</div>
                </div>
            </div>
            
            <div class='leaderboard-container'>
                <div class='table-header'>
                    <h2>🏅 Top Players Ranking</h2>
                </div>";
        
        if (!empty($leaderboardData)) {
            $html .= "
                <table>
                    <thead>
                        <tr>
                            <th style='width: 60px;'>Rank</th>
                            <th>Player Name</th>
                            <th style='width: 100px;'>Best Score</th>
                            <th style='width: 80px;'>Best Wave</th>
                            <th style='width: 80px;'>Games Played</th>
                            <th style='width: 100px;'>Last Game</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($leaderboardData as $index => $player) {
                $rank = $index + 1;
                $rankClass = $rank <= 3 ? "rank-{$rank}" : "";
                $rankDisplay = $rank <= 3 ? ["🥇", "🥈", "🥉"][$rank - 1] . " {$rank}" : $rank;
                
                $html .= "
                    <tr>
                        <td class='rank {$rankClass}'>{$rankDisplay}</td>
                        <td class='player-name'>" . htmlspecialchars($player['full_name'] ?: $player['email']) . "</td>
                        <td class='score'>" . number_format($player['best_score']) . "</td>
                        <td class='wave'>Wave " . $player['best_wave'] . "</td>
                        <td class='games-count'>" . $player['games_played'] . "</td>
                        <td class='last-game'>" . date('M j, Y', strtotime($player['last_game'])) . "</td>
                    </tr>";
            }
            
            $html .= "
                    </tbody>
                </table>";
        } else {
            $html .= "
                <div class='no-data'>
                    <h3>No game data available</h3>
                    <p>No players have completed games yet.</p>
                </div>";
        }
        
        $html .= "
            </div>
            
            <div class='footer'>
                <p>Aprender - Gamified Problem-Based Learning System</p>
                <p>This report was automatically generated on " . date('F j, Y \a\t g:i A') . "</p>
                <p>Visit our website to start playing and climb the leaderboard!</p>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    public function generateUserReport($userId, $userData, $gameHistory) {
        $html = $this->generateUserReportHTML($userId, $userData, $gameHistory);
        
        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();
        
        $filename = 'Aprender_User_Report_' . date('Y-m-d_H-i-s') . '.pdf';
        $this->dompdf->stream($filename, [
            'Attachment' => 0
        ]);
    }
    
    private function generateUserReportHTML($userId, $userData, $gameHistory) {
        $currentDate = date('F j, Y');
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>User Report - {$userData['full_name']}</title>
            <style>
                * {
                    color: #333 !important;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: #f5f5f5 !important;
                    color: #333 !important;
                }
                
                .header {
                    background: #667eea;
                    color: white !important;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }
                
                .header h1 {
                    margin: 0;
                    font-size: 2rem;
                    font-weight: bold;
                    color: white !important;
                }
                
                .header p {
                    color: white !important;
                }
                
                .content {
                    background: white;
                    padding: 30px;
                    border-radius: 0 0 10px 10px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                
                .user-info {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                }
                
                .stats-grid {
                    display: flex;
                    justify-content: space-around;
                    margin: 20px 0;
                }
                
                .stat-item {
                    text-align: center;
                    padding: 15px;
                }
                
                .stat-number {
                    font-size: 1.8rem;
                    font-weight: bold;
                    color: #667eea;
                }
                
                .stat-label {
                    color: #666;
                    font-size: 0.9rem;
                }
                
                .game-history {
                    margin-top: 30px;
                }
                
                .game-history h3 {
                    color: #333;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 10px;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                }
                
                th, td {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #e0e0e0;
                }
                
                th {
                    background: #667eea;
                    color: white;
                }
                
                .footer {
                    text-align: center;
                    padding: 20px;
                    color: #666;
                    font-size: 0.8rem;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>📊 User Report</h1>
                <p>Generated on {$currentDate}</p>
            </div>
            
            <div class='content'>
                <div class='user-info'>
                    <h2>👤 Player Information</h2>
                    <p><strong>Name:</strong> " . htmlspecialchars($userData['full_name']) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($userData['email']) . "</p>
                    <p><strong>Member Since:</strong> " . date('F j, Y', strtotime($userData['created_at'])) . "</p>
                </div>
                
                <div class='stats-grid'>
                    <div class='stat-item'>
                        <div class='stat-number'>" . $userData['best_score'] . "</div>
                        <div class='stat-label'>Best Score</div>
                    </div>
                    <div class='stat-item'>
                        <div class='stat-number'>" . $userData['best_wave'] . "</div>
                        <div class='stat-label'>Best Wave</div>
                    </div>
                    <div class='stat-item'>
                        <div class='stat-number'>" . $userData['games_played'] . "</div>
                        <div class='stat-label'>Games Played</div>
                    </div>
                </div>";
        
        if (!empty($gameHistory)) {
            $html .= "
                <div class='game-history'>
                    <h3>🎮 Recent Game History</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Wave Reached</th>
                            </tr>
                        </thead>
                        <tbody>";
            
            foreach ($gameHistory as $game) {
                $html .= "
                    <tr>
                        <td>" . date('M j, Y g:i A', strtotime($game['created_at'])) . "</td>
                        <td>" . number_format($game['score']) . "</td>
                        <td>Wave " . $game['wave_reached'] . "</td>
                    </tr>";
            }
            
            $html .= "
                        </tbody>
                    </table>
                </div>";
        }
        
        $html .= "
            </div>
            
            <div class='footer'>
                <p>Aprender - Gamified Problem-Based Learning System</p>
                <p>This report was automatically generated on " . date('F j, Y \a\t g:i A') . "</p>
            </div>
        </body>
        </html>";
        
        return $html;
    }
}
