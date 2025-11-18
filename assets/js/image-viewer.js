/**
 * Image Viewer JavaScript
 * Handles image viewing functionality in modals
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add image-modal class to all image modals
    const imageModals = document.querySelectorAll('#imageModal');
    imageModals.forEach(modal => {
        modal.classList.add('image-modal');
    });

    // Initialize zoom functionality for image modals
    initializeImageZoom();
});

/**
 * Initialize zoom functionality for images in modals
 */
function initializeImageZoom() {
    // Find all image modals
    const imageModals = document.querySelectorAll('.image-modal');
    
    imageModals.forEach(modal => {
        const modalBody = modal.querySelector('.modal-body');
        const img = modalBody ? modalBody.querySelector('img') : null;
        
        if (img) {
            // Create zoom controls if they don't exist
            if (!modal.querySelector('.image-zoom-controls')) {
                const zoomControls = document.createElement('div');
                zoomControls.className = 'image-zoom-controls';
                zoomControls.innerHTML = `
                    <button type="button" class="zoom-in" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="zoom-out" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button type="button" class="zoom-reset" title="Reset Zoom">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                `;
                modalBody.appendChild(zoomControls);
                
                // Initialize zoom level
                img.style.transform = 'scale(1)';
                img.style.transition = 'transform 0.3s ease';
                
                // Zoom in button
                zoomControls.querySelector('.zoom-in').addEventListener('click', function() {
                    const currentScale = parseFloat(img.style.transform.replace('scale(', '').replace(')', '') || 1);
                    const newScale = currentScale + 0.1;
                    img.style.transform = `scale(${newScale})`;
                });
                
                // Zoom out button
                zoomControls.querySelector('.zoom-out').addEventListener('click', function() {
                    const currentScale = parseFloat(img.style.transform.replace('scale(', '').replace(')', '') || 1);
                    const newScale = Math.max(0.5, currentScale - 0.1);
                    img.style.transform = `scale(${newScale})`;
                });
                
                // Reset zoom button
                zoomControls.querySelector('.zoom-reset').addEventListener('click', function() {
                    img.style.transform = 'scale(1)';
                });
                
                // Reset zoom when modal is closed
                modal.addEventListener('hidden.bs.modal', function() {
                    img.style.transform = 'scale(1)';
                });
            }
        }
    });
}

/**
 * Election Results Print & Export Functionality
 * This script handles the printing and exporting of election results
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize print preview mode
    initPrintPreview();
    
    // Initialize export functionality
    initExportFunctions();
});

/**
 * Initialize print preview functionality
 */
function initPrintPreview() {
    // Check if we're on the election results page
    const printButton = document.getElementById('print-results');
    if (!printButton) return;
    
    // Add print header and footer to the page
    addPrintElements();
    
    // Handle print button click
    printButton.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Add print class to body to show print-only elements in preview
        document.body.classList.add('print-preview-mode');
        
        // Show print dialog after a short delay to allow styles to apply
        setTimeout(() => {
            window.print();
            
            // Remove print preview class after printing
            setTimeout(() => {
                document.body.classList.remove('print-preview-mode');
            }, 1000);
        }, 300);
    });
    
    // Listen for print media change
    const mediaQueryList = window.matchMedia('print');
    mediaQueryList.addEventListener('change', function(mql) {
        if (mql.matches) {
            // Entering print mode
            document.body.classList.add('printing');
        } else {
            // Exiting print mode
            document.body.classList.remove('printing');
            document.body.classList.remove('print-preview-mode');
        }
    });
}

/**
 * Add print-specific elements to the page
 */
function addPrintElements() {
    // Get election title from the page
    let electionTitle = '';
    const titleElement = document.querySelector('.card-title');
    if (titleElement) {
        const titleText = titleElement.textContent || '';
        const titleMatch = titleText.match(/Results Overview: (.*)/);
        if (titleMatch && titleMatch[1]) {
            electionTitle = titleMatch[1].trim();
        }
    }
    
    // Create print header
    const printHeader = document.createElement('div');
    printHeader.className = 'print-header';
    printHeader.innerHTML = `
        <h1>Valley View University</h1>
        <h2>Students' Representative Council</h2>
        <h3>${electionTitle}</h3>
    `;
    
    // Create print footer
    const printFooter = document.createElement('div');
    printFooter.className = 'print-footer';
    
    // Create print container
    const printContainer = document.createElement('div');
    printContainer.className = 'print-container';
    
    // Get content container
    const contentContainer = document.querySelector('.container-fluid');
    if (!contentContainer) return;
    
    // Insert print elements
    document.body.insertBefore(printContainer, contentContainer);
    printContainer.appendChild(printHeader);
    printContainer.appendChild(contentContainer.cloneNode(true));
    printContainer.appendChild(printFooter);
    
    // Hide original content when in print preview mode
    const style = document.createElement('style');
    style.textContent = `
        .print-preview-mode .container-fluid:not(.print-container .container-fluid) {
            display: none;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Initialize export functionality
 */
function initExportFunctions() {
    // Export as PDF
    const exportPdfButton = document.getElementById('export-pdf');
    if (exportPdfButton) {
        exportPdfButton.addEventListener('click', function(e) {
            e.preventDefault();
            exportToPdf();
        });
    }
    
    // Export as CSV
    const exportCsvButton = document.getElementById('export-csv');
    if (exportCsvButton) {
        exportCsvButton.addEventListener('click', function(e) {
            e.preventDefault();
            exportToCsv();
        });
    }
}

/**
 * Export election results to PDF
 */
function exportToPdf() {
    // Show loading overlay
    const loadingOverlay = createLoadingOverlay('Generating PDF...');
    document.body.appendChild(loadingOverlay);
    
    // Load required libraries dynamically
    Promise.all([
        loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'),
        loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js')
    ]).then(() => {
        // Get election title
        let electionTitle = getElectionTitle();
        
        // Create a clone of the content for PDF
        const contentElement = document.querySelector('.container-fluid');
        const clone = contentElement.cloneNode(true);
        
        // Add print elements to the clone
        const pdfContainer = document.createElement('div');
        pdfContainer.style.backgroundColor = 'white';
        pdfContainer.style.padding = '0';
        pdfContainer.style.width = '100%';
        
        // Create header
        const pdfHeader = document.createElement('div');
        pdfHeader.style.background = 'linear-gradient(135deg, #4e54c8, #8f94fb)';
        pdfHeader.style.padding = '30px 20px';
        pdfHeader.style.color = 'white';
        pdfHeader.style.textAlign = 'center';
        
        pdfHeader.innerHTML = `
            <h1 style="font-size: 28px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0; color: white;">
                Valley View University
            </h1>
            <h2 style="font-size: 18px; font-weight: 500; letter-spacing: 0.5px; margin: 0 0 15px 0; color: white;">
                Students' Representative Council
            </h2>
            <h3 style="font-size: 22px; font-weight: 600; background-color: rgba(255, 255, 255, 0.2); display: inline-block; padding: 8px 20px; border-radius: 30px; margin: 0; color: white;">
                ${electionTitle}
            </h3>
        `;
        
        // Remove elements that shouldn't be in the PDF
        const elementsToRemove = clone.querySelectorAll('.breadcrumb, .btn, .navbar, .sidebar, footer, .header-actions, #adminActionsDropdown, .dropdown-menu, .no-print');
        elementsToRemove.forEach(el => el.remove());
        
        // Add elements to container
        pdfContainer.appendChild(pdfHeader);
        pdfContainer.appendChild(clone);
        
        // Add footer
        const pdfFooter = document.createElement('div');
        pdfFooter.style.height = '10px';
        pdfFooter.style.background = 'linear-gradient(90deg, #4e54c8, #8f94fb)';
        pdfFooter.style.marginTop = '20px';
        pdfContainer.appendChild(pdfFooter);
        
        // Add the container to the document temporarily (hidden)
        pdfContainer.style.position = 'absolute';
        pdfContainer.style.left = '-9999px';
        document.body.appendChild(pdfContainer);
        
        // Use html2canvas to capture the content
        html2canvas(pdfContainer, {
            scale: 2, // Higher scale for better quality
            useCORS: true,
            logging: false,
            allowTaint: true
        }).then(canvas => {
            // Remove the temporary container
            document.body.removeChild(pdfContainer);
            
            // Create PDF
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 295; // A4 height in mm
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;
            
            // Add image to PDF
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            // Add new pages if content is longer than one page
            while (heightLeft > 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            // Save the PDF
            pdf.save(`${electionTitle.replace(/\s+/g, '_')}_results.pdf`);
            
            // Remove loading overlay
            document.body.removeChild(loadingOverlay);
        }).catch(err => {
            console.error('Error generating PDF:', err);
            alert('There was an error generating the PDF. Please try again or use the print option instead.');
            document.body.removeChild(loadingOverlay);
        });
    }).catch(err => {
        console.error('Error loading scripts:', err);
        alert('There was an error loading required scripts. Please try again or use the print option instead.');
        document.body.removeChild(loadingOverlay);
    });
}

/**
 * Export election results to CSV
 */
function exportToCsv() {
    // Get election title
    let electionTitle = getElectionTitle();
    
    // Gather all candidate data
    const csvData = [];
    csvData.push(['Position', 'Candidate', 'Votes', 'Percentage', 'Status']);
    
    document.querySelectorAll('.card.mb-4:not(:first-child)').forEach(positionCard => {
        const positionTitle = positionCard.querySelector('.card-title').textContent.trim();
        const candidateCards = positionCard.querySelectorAll('.candidate-result-card');
        
        candidateCards.forEach(card => {
            const candidateName = card.querySelector('h5').textContent.trim();
            const votes = card.querySelector('.votes-count strong').textContent.trim();
            const percentage = card.querySelector('.votes-percentage strong').textContent.trim();
            const isWinner = card.classList.contains('winner') ? 'Elected' : '';
            
            csvData.push([positionTitle, candidateName, votes, percentage, isWinner]);
        });
    });
    
    // Convert to CSV format
    const csvContent = csvData.map(row => row.join(',')).join('\n');
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    
    link.setAttribute('href', url);
    link.setAttribute('download', `${electionTitle.replace(/\s+/g, '_')}_results.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Get the election title from the page
 */
function getElectionTitle() {
    let electionTitle = 'Election Results';
    const titleElement = document.querySelector('.card-title');
    if (titleElement) {
        const titleText = titleElement.textContent || '';
        const titleMatch = titleText.match(/Results Overview: (.*)/);
        if (titleMatch && titleMatch[1]) {
            electionTitle = titleMatch[1].trim();
        }
    }
    return electionTitle;
}

/**
 * Create a loading overlay
 */
function createLoadingOverlay(message) {
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
    overlay.style.display = 'flex';
    overlay.style.justifyContent = 'center';
    overlay.style.alignItems = 'center';
    overlay.style.zIndex = '9999';
    
    const spinner = document.createElement('div');
    spinner.innerHTML = `
        <i class="fas fa-spinner fa-spin fa-3x"></i>
        <p style="margin-top: 10px;">${message}</p>
    `;
    spinner.style.textAlign = 'center';
    
    overlay.appendChild(spinner);
    return overlay;
}

/**
 * Load a script dynamically
 */
function loadScript(src) {
    return new Promise((resolve, reject) => {
        // Check if script is already loaded
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }
        
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.body.appendChild(script);
    });
} 