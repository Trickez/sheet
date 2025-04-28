<?php
class CutlistOptimizer {
    private $sheetWidth;
    private $sheetHeight;
    private $cuts = [];
    private $optimizedSheets = [];
    
    public function __construct($width, $height) {
        $this->sheetWidth = $width;
        $this->sheetHeight = $height;
    }
    
    public function addCut($width, $height, $quantity) {
        for ($i = 0; $i < $quantity; $i++) {
            $this->cuts[] = [
                'width' => $width,
                'height' => $height,
                'placed' => false
            ];
        }
    }
    
    public function optimize() {
        usort($this->cuts, function($a, $b) {
            $areaA = $a['width'] * $a['height'];
            $areaB = $b['width'] * $b['height'];
            return $areaB - $areaA;
        });
        
        while (count(array_filter($this->cuts, function($cut) { return !$cut['placed']; })) > 0) {
            $this->optimizedSheets[] = $this->placeCutsOnNewSheet();
        }
        
        return $this->optimizedSheets;
    }
    
    private function placeCutsOnNewSheet() {
        $sheet = [
            'width' => $this->sheetWidth,
            'height' => $this->sheetHeight,
            'cuts' => [],
            'remaining' => [
                ['x' => 0, 'y' => 0, 'width' => $this->sheetWidth, 'height' => $this->sheetHeight]
            ]
        ];
        
        foreach ($this->cuts as &$cut) {
            if ($cut['placed']) continue;
            
            foreach ($sheet['remaining'] as $key => $space) {
                if ($cut['width'] <= $space['width'] && $cut['height'] <= $space['height']) {
                    $cut['x'] = $space['x'];
                    $cut['y'] = $space['y'];
                    $cut['placed'] = true;
                    $sheet['cuts'][] = $cut;
                    
                    unset($sheet['remaining'][$key]);
                    
                    if ($space['width'] > $cut['width']) {
                        $sheet['remaining'][] = [
                            'x' => $space['x'] + $cut['width'],
                            'y' => $space['y'],
                            'width' => $space['width'] - $cut['width'],
                            'height' => $cut['height']
                        ];
                    }
                    
                    if ($space['height'] > $cut['height']) {
                        $sheet['remaining'][] = [
                            'x' => $space['x'],
                            'y' => $space['y'] + $cut['height'],
                            'width' => $space['width'],
                            'height' => $space['height'] - $cut['height']
                        ];
                    }
                    
                    $sheet['remaining'] = array_values($sheet['remaining']);
                    break;
                }
                
                if ($cut['height'] <= $space['width'] && $cut['width'] <= $space['height']) {
                    $cut['x'] = $space['x'];
                    $cut['y'] = $space['y'];
                    $cut['placed'] = true;
                    $cut['rotated'] = true;
                    $sheet['cuts'][] = $cut;
                    
                    unset($sheet['remaining'][$key]);
                    
                    if ($space['width'] > $cut['height']) {
                        $sheet['remaining'][] = [
                            'x' => $space['x'] + $cut['height'],
                            'y' => $space['y'],
                            'width' => $space['width'] - $cut['height'],
                            'height' => $cut['width']
                        ];
                    }
                    
                    if ($space['height'] > $cut['width']) {
                        $sheet['remaining'][] = [
                            'x' => $space['x'],
                            'y' => $space['y'] + $cut['width'],
                            'width' => $space['width'],
                            'height' => $space['height'] - $cut['width']
                        ];
                    }
                    
                    $sheet['remaining'] = array_values($sheet['remaining']);
                    break;
                }
            }
        }
        
        return $sheet;
    }
    
    public function displayResults() {
        $totalSheets = count($this->optimizedSheets);
        $totalCuts = count($this->cuts);
        $totalArea = $totalSheets * $this->sheetWidth * $this->sheetHeight;
        $usedArea = 0;
        
        foreach ($this->cuts as $cut) {
            $usedArea += $cut['width'] * $cut['height'];
        }
        
        $wastePercentage = round((($totalArea - $usedArea) / $totalArea) * 100, 2);
        ?>
        <div class="bg-gray-100 p-4 rounded-lg shadow mb-6">
            <h2 class="text-xl font-bold mb-2">Optimization Results</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-3 rounded shadow">
                    <p class="font-semibold">Sheets used:</p>
                    <p class="text-2xl"><?= $totalSheets ?></p>
                </div>
                <div class="bg-white p-3 rounded shadow">
                    <p class="font-semibold">Total pieces:</p>
                    <p class="text-2xl"><?= $totalCuts ?></p>
                </div>
                <div class="bg-white p-3 rounded shadow">
                    <p class="font-semibold">Material utilization:</p>
                    <p class="text-2xl"><?= (100 - $wastePercentage) ?>%</p>
                    <p class="text-sm text-gray-600">Waste: <?= $wastePercentage ?>%</p>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($this->optimizedSheets as $sheetNum => $sheet): ?>
                <?php
                $scale = min(300 / $sheet['width'], 300 / $sheet['height']);
                $displayWidth = $sheet['width'] * $scale;
                $displayHeight = $sheet['height'] * $scale;
                ?>
                <div class="bg-white p-4 rounded-lg shadow">
                    <h3 class="text-lg font-semibold mb-3">Sheet <?= $sheetNum + 1 ?></h3>
                    <div class="relative mx-auto border-2 border-gray-800 bg-gray-50" 
                         style="width: <?= $displayWidth ?>px; height: <?= $displayHeight ?>px;">
                        <?php foreach ($sheet['cuts'] as $cut): ?>
                            <?php
                            $width = (isset($cut['rotated']) && $cut['rotated']) ? $cut['height'] * $scale : $cut['width'] * $scale;
                            $height = (isset($cut['rotated']) && $cut['rotated']) ? $cut['width'] * $scale : $cut['height'] * $scale;
                            $left = $cut['x'] * $scale;
                            $top = $cut['y'] * $scale;
                            $bgColor = (isset($cut['rotated']) && $cut['rotated']) ? 'bg-blue-200' : 'bg-green-200';
                            ?>
                            <div class="absolute border border-gray-700 flex flex-col items-center justify-center text-xs overflow-hidden <?= $bgColor ?>" 
                                 style="width: <?= $width ?>px; height: <?= $height ?>px; left: <?= $left ?>px; top: <?= $top ?>px;">
                                <span><?= $cut['width'] ?> × <?= $cut['height'] ?></span>
                                <?php if (isset($cut['rotated']) && $cut['rotated']): ?>
                                    <span class="text-xs">(R)</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sheetWidth = floatval($_POST['sheet_width']);
    $sheetHeight = floatval($_POST['sheet_height']);
    
    $optimizer = new CutlistOptimizer($sheetWidth, $sheetHeight);
    
    foreach ($_POST['cuts'] as $cut) {
        if (!empty($cut['width']) && !empty($cut['height']) && !empty($cut['quantity'])) {
            $optimizer->addCut(
                floatval($cut['width']),
                floatval($cut['height']),
                intval($cut['quantity'])
            );
        }
    }
    
    $optimizer->optimize();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sheet Cutlist Optimizer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Sheet Cutlist Optimizer</h1>
            <p class="text-gray-600">Minimize material waste when cutting sheets</p>
        </header>
        
        <form method="post" class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="sheet_width" class="block text-sm font-medium text-gray-700 mb-1">Sheet Width</label>
                    <input type="number" step="0.01" name="sheet_width" id="sheet_width" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                           value="<?= $_POST['sheet_width'] ?? 2050 ?>" required>
                </div>
                <div>
                    <label for="sheet_height" class="block text-sm font-medium text-gray-700 mb-1">Sheet Height</label>
                    <input type="number" step="0.01" name="sheet_height" id="sheet_height" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                           value="<?= $_POST['sheet_height'] ?? 3050 ?>" required>
                </div>
            </div>
            
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Cuts</h2>
            <div class="overflow-x-auto">
                <table id="cuts-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Width</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Height</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (isset($_POST['cuts'])): ?>
                            <?php foreach ($_POST['cuts'] as $i => $cut): ?>
                                <tr>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" name="cuts[<?= $i ?>][width]" value="<?= $cut['width'] ?>"
                                               class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" name="cuts[<?= $i ?>][height]" value="<?= $cut['height'] ?>"
                                               class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" name="cuts[<?= $i ?>][quantity]" value="<?= $cut['quantity'] ?>" min="1"
                                               class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
                                    </td>
                                    <td class="px-4 py-2">
                                        <button type="button" class="remove-cut text-red-600 hover:text-red-800 text-sm font-medium">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="px-4 py-2">
                                    <input type="number" step="0.01" name="cuts[0][width]"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" step="0.01" name="cuts[0][height]"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" name="cuts[0][quantity]" value="1" min="1"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
                                </td>
                                <td class="px-4 py-2">
                                    <button type="button" class="remove-cut text-red-600 hover:text-red-800 text-sm font-medium">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" id="add-cut" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Add Cut
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Optimize
                </button>
            </div>
        </form>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($optimizer)): ?>
            <div class="results-section">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Results</h2>
                <?php $optimizer->displayResults(); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add new cut row
            document.getElementById('add-cut').addEventListener('click', function() {
                const table = document.getElementById('cuts-table').getElementsByTagName('tbody')[0];
                const rowCount = table.rows.length;
                const newRow = table.insertRow();
                
                const widthCell = newRow.insertCell(0);
                widthCell.className = "px-4 py-2";
                widthCell.innerHTML = '<input type="number" step="0.01" name="cuts[' + rowCount + '][width]" class="w-full px-2 py-1 border border-gray-300 rounded-md" required>';
                
                const heightCell = newRow.insertCell(1);
                heightCell.className = "px-4 py-2";
                heightCell.innerHTML = '<input type="number" step="0.01" name="cuts[' + rowCount + '][height]" class="w-full px-2 py-1 border border-gray-300 rounded-md" required>';
                
                const qtyCell = newRow.insertCell(2);
                qtyCell.className = "px-4 py-2";
                qtyCell.innerHTML = '<input type="number" name="cuts[' + rowCount + '][quantity]" value="1" min="1" class="w-full px-2 py-1 border border-gray-300 rounded-md" required>';
                
                const actionCell = newRow.insertCell(3);
                actionCell.className = "px-4 py-2";
                actionCell.innerHTML = '<button type="button" class="remove-cut text-red-600 hover:text-red-800 text-sm font-medium">Remove</button>';
                
                actionCell.querySelector('.remove-cut').addEventListener('click', function() {
                    table.deleteRow(newRow.rowIndex - 1);
                });
            });
            
            document.querySelectorAll('.remove-cut').forEach(function(button) {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    row.parentNode.removeChild(row);
                });
            });
        });
    </script>
</body>
</html>