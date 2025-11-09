<?php
require_once 'config/config.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    die('Invalid product');
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product || !$product['file_path']) {
    die('Product not found or file not available');
}

// Check if user has purchased
$hasPurchased = false;
$maxPages = $product['preview_pages'];

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT oi.* FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'completed'
    ");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $hasPurchased = true;
        $maxPages = $product['total_pages'];
    }
}

$pdfPath = $product['file_path'];
if (!file_exists($pdfPath)) {
    die('File not found');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Viewer</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #525659;
            font-family: Arial, sans-serif;
            transition: padding 0.3s;
        }
        
        /* Fullscreen mode styles */
        body:fullscreen {
            padding: 10px;
            background: #2c2c2c;
        }
        
        body:-webkit-full-screen {
            padding: 10px;
            background: #2c2c2c;
        }
        
        body:-moz-full-screen {
            padding: 10px;
            background: #2c2c2c;
        }
        
        body:fullscreen #controls {
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        #controls {
            background: white;
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
            border-radius: 5px;
        }
        #controls button {
            padding: 8px 15px;
            margin: 0 5px;
            cursor: pointer;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 3px;
        }
        #controls button:hover {
            background: #e9ecef;
        }
        #controls button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        #pageInfo {
            display: inline-block;
            margin: 0 15px;
        }
        #pdfCanvas {
            display: block;
            margin: 0 auto;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            image-rendering: high-quality;
            image-rendering: -webkit-optimize-contrast;
        }
        .restricted-message {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            margin: 20px auto;
            max-width: 800px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div id="controls">
        <button id="prevPage">◀ Previous</button>
        <span id="pageInfo">
            Page <span id="currentPage">1</span> of <span id="totalPages">-</span>
        </span>
        <button id="nextPage">Next ▶</button>
        <span style="margin: 0 10px;">|</span>
        <button id="zoomOut">🔍−</button>
        <button id="fitWidth">Fit Width</button>
        <button id="zoomIn">🔍+</button>
        <span id="zoomLevel" style="margin-left: 10px; font-size: 12px;">100%</span>
        <span style="margin: 0 10px;">|</span>
        <button id="fullscreenBtn">⛶ Fullscreen</button>
    </div>
    
    <?php if (!$hasPurchased && $maxPages > 0): ?>
    <div class="restricted-message">
        <strong>Preview Mode:</strong> You can view <?php echo $maxPages; ?> of <?php echo $product['total_pages']; ?> pages. 
        Purchase to unlock full access.
    </div>
    <?php endif; ?>
    
    <canvas id="pdfCanvas"></canvas>

    <script>
        const pdfPath = '<?php echo $pdfPath; ?>';
        const maxPages = <?php echo $maxPages; ?>;
        const hasPurchased = <?php echo $hasPurchased ? 'true' : 'false'; ?>;
        
        let pdfDoc = null;
        let currentPage = 1;
        let scale = 2.0; // Increased for better quality
        const canvas = document.getElementById('pdfCanvas');
        const ctx = canvas.getContext('2d');

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // Load PDF with high quality settings
        console.log('Loading PDF from:', pdfPath);
        const loadingTask = pdfjsLib.getDocument({
            url: pdfPath,
            cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
            cMapPacked: true,
            enableXfa: true
        });
        
        loadingTask.promise.then(function(pdf) {
            console.log('PDF loaded successfully, pages:', pdf.numPages);
            pdfDoc = pdf;
            document.getElementById('totalPages').textContent = hasPurchased ? pdf.numPages : maxPages;
            renderPage(currentPage);
        }).catch(function(error) {
            console.error('Error loading PDF:', error);
            document.body.innerHTML = '<div style="padding:20px;text-align:center;"><h2>Error Loading PDF</h2><p>' + error.message + '</p><p>Path: ' + pdfPath + '</p></div>';
        });

        function renderPage(pageNum) {
            // Check if page is accessible
            if (!hasPurchased && pageNum > maxPages) {
                alert('This page is not available in preview mode. Please purchase to access full content.');
                currentPage = maxPages;
                pageNum = maxPages;
            }

            pdfDoc.getPage(pageNum).then(function(page) {
                // Use device pixel ratio for retina displays
                const devicePixelRatio = window.devicePixelRatio || 1;
                const viewport = page.getViewport({ scale: scale });
                
                // Set canvas size for high DPI displays
                canvas.width = viewport.width * devicePixelRatio;
                canvas.height = viewport.height * devicePixelRatio;
                canvas.style.width = viewport.width + 'px';
                canvas.style.height = viewport.height + 'px';
                
                // Scale context for high DPI
                ctx.setTransform(devicePixelRatio, 0, 0, devicePixelRatio, 0, 0);

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport,
                    intent: 'print' // Use print quality instead of display
                };

                page.render(renderContext).promise.then(function() {
                    console.log('Page rendered in high quality');
                });
                
                document.getElementById('currentPage').textContent = pageNum;
                updateButtons();
            });
        }

        function updateButtons() {
            document.getElementById('prevPage').disabled = currentPage <= 1;
            const maxAllowedPage = hasPurchased ? pdfDoc.numPages : maxPages;
            document.getElementById('nextPage').disabled = currentPage >= maxAllowedPage;
        }

        document.getElementById('prevPage').addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
            }
        });

        document.getElementById('nextPage').addEventListener('click', function() {
            const maxAllowedPage = hasPurchased ? pdfDoc.numPages : maxPages;
            if (currentPage < maxAllowedPage) {
                currentPage++;
                renderPage(currentPage);
            }
        });

        function updateZoomLevel() {
            const percentage = Math.round(scale * 50); // 2.0 scale = 100%
            document.getElementById('zoomLevel').textContent = percentage + '%';
        }

        document.getElementById('zoomIn').addEventListener('click', function() {
            if (scale < 4.0) { // Max zoom 4x
                scale += 0.25;
                renderPage(currentPage);
                updateZoomLevel();
            }
        });

        document.getElementById('zoomOut').addEventListener('click', function() {
            if (scale > 0.75) { // Min zoom 0.75x
                scale -= 0.25;
                renderPage(currentPage);
                updateZoomLevel();
            }
        });
        
        document.getElementById('fitWidth').addEventListener('click', function() {
            // Calculate scale to fit width
            pdfDoc.getPage(currentPage).then(function(page) {
                const viewport = page.getViewport({ scale: 1.0 });
                const containerWidth = window.innerWidth - 40; // 20px padding each side
                scale = containerWidth / viewport.width;
                renderPage(currentPage);
                updateZoomLevel();
            });
        });
        
        // Fullscreen functionality
        document.getElementById('fullscreenBtn').addEventListener('click', function() {
            toggleFullscreen();
        });
        
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                // Enter fullscreen
                document.documentElement.requestFullscreen().then(function() {
                    document.getElementById('fullscreenBtn').innerHTML = '⛶ Exit Fullscreen';
                    // Auto fit to width in fullscreen
                    setTimeout(function() {
                        document.getElementById('fitWidth').click();
                    }, 100);
                }).catch(function(err) {
                    console.error('Error entering fullscreen:', err);
                });
            } else {
                // Exit fullscreen
                document.exitFullscreen().then(function() {
                    document.getElementById('fullscreenBtn').innerHTML = '⛶ Fullscreen';
                });
            }
        }
        
        // Listen for fullscreen changes (ESC key, etc.)
        document.addEventListener('fullscreenchange', function() {
            if (!document.fullscreenElement) {
                document.getElementById('fullscreenBtn').innerHTML = '⛶ Fullscreen';
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                document.getElementById('prevPage').click();
            } else if (e.key === 'ArrowRight' || e.key === 'PageDown') {
                document.getElementById('nextPage').click();
            } else if (e.key === '+' || e.key === '=') {
                document.getElementById('zoomIn').click();
            } else if (e.key === '-') {
                document.getElementById('zoomOut').click();
            } else if (e.key === '0') {
                document.getElementById('fitWidth').click();
            } else if (e.key === 'f' || e.key === 'F') {
                toggleFullscreen();
            }
        });
        
        // Initial zoom level display
        updateZoomLevel();
    </script>
</body>
</html>
