/**
 * Ninya Flower Shop - Admin Dashboard SVG Chart Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    const chartContainer = document.getElementById('analytics-chart');
    if (!chartContainer) return;
    
    // Extract JSON data embedded in the data attributes
    const rawData = chartContainer.getAttribute('data-points');
    const labels = JSON.parse(chartContainer.getAttribute('data-labels') || '[]');
    let points = [];
    
    try {
        points = JSON.parse(rawData || '[]');
    } catch (e) {
        points = [];
    }
    
    if (points.length === 0) {
        chartContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#7E7471;font-size:0.9rem;">No data available for plotting.</div>';
        return;
    }
    
    renderSVGChart(chartContainer, points, labels);
    
    // Re-render on resize
    window.addEventListener('resize', () => {
        renderSVGChart(chartContainer, points, labels);
    });
});

function renderSVGChart(container, data, labels) {
    const width = container.offsetWidth;
    const height = container.offsetHeight;
    const padding = { top: 30, right: 30, bottom: 40, left: 60 };
    
    // Clear previous contents
    container.innerHTML = '';
    
    // Create the master SVG element
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    svg.setAttribute('style', 'overflow: visible;');
    
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    
    // Find min and max values
    const maxVal = Math.max(...data) * 1.15 || 100; // Leave 15% head room
    const minVal = 0;
    
    // 1. Draw Grid Lines & Y-Axis Labels
    const yLines = 5;
    for (let i = 0; i <= yLines; i++) {
        const ratio = i / yLines;
        const y = padding.top + chartHeight * (1 - ratio);
        const val = minVal + (maxVal - minVal) * ratio;
        
        // Grid lines
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', padding.left);
        line.setAttribute('y1', y);
        line.setAttribute('x2', width - padding.right);
        line.setAttribute('y2', y);
        line.setAttribute('stroke', '#E2E8F0');
        line.setAttribute('stroke-width', '1');
        if (i > 0) line.setAttribute('stroke-dasharray', '4');
        svg.appendChild(line);
        
        // Text labels
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', padding.left - 12);
        text.setAttribute('y', y + 4);
        text.setAttribute('text-anchor', 'end');
        text.setAttribute('font-size', '10');
        text.setAttribute('fill', '#7E7471');
        text.setAttribute('font-family', 'Inter, sans-serif');
        text.textContent = '$' + Math.round(val);
        svg.appendChild(text);
    }
    
    // 2. Draw X-Axis Labels & Vertical Ticks
    const xPointsCount = data.length;
    const xCoords = [];
    
    for (let i = 0; i < xPointsCount; i++) {
        const ratio = xPointsCount > 1 ? i / (xPointsCount - 1) : 0.5;
        const x = padding.left + chartWidth * ratio;
        xCoords.push(x);
        
        // Label
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', x);
        text.setAttribute('y', height - padding.bottom + 20);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-size', '10');
        text.setAttribute('fill', '#7E7471');
        text.setAttribute('font-family', 'Inter, sans-serif');
        text.textContent = labels[i] || `Day ${i + 1}`;
        svg.appendChild(text);
    }
    
    // 3. Map Data Points to Y Coordinates
    const points = [];
    for (let i = 0; i < xPointsCount; i++) {
        const ratio = (data[i] - minVal) / (maxVal - minVal);
        const y = padding.top + chartHeight * (1 - ratio);
        points.push({ x: xCoords[i], y: y, value: data[i] });
    }
    
    // 4. Generate Bezier Curve Path (Catmull-Rom or Simple Curved line)
    let pathD = '';
    let fillD = '';
    
    if (points.length > 0) {
        pathD = `M ${points[0].x} ${points[0].y}`;
        fillD = `M ${points[0].x} ${height - padding.bottom} L ${points[0].x} ${points[0].y}`;
        
        for (let i = 1; i < points.length; i++) {
            const cpX1 = points[i-1].x + (points[i].x - points[i-1].x) / 2;
            const cpY1 = points[i-1].y;
            const cpX2 = points[i-1].x + (points[i].x - points[i-1].x) / 2;
            const cpY2 = points[i].y;
            
            pathD += ` C ${cpX1} ${cpY1}, ${cpX2} ${cpY2}, ${points[i].x} ${points[i].y}`;
            fillD += ` C ${cpX1} ${cpY1}, ${cpX2} ${cpY2}, ${points[i].x} ${points[i].y}`;
        }
        
        fillD += ` L ${points[points.length - 1].x} ${height - padding.bottom} Z`;
    }
    
    // 5. Draw Shadow Fill Area
    if (fillD) {
        const fillPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        fillPath.setAttribute('d', fillD);
        fillPath.setAttribute('fill', 'url(#chartGradient)');
        svg.appendChild(fillPath);
        
        // Define Gradient inside SVG
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const linearGrad = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
        linearGrad.setAttribute('id', 'chartGradient');
        linearGrad.setAttribute('x1', '0');
        linearGrad.setAttribute('y1', '0');
        linearGrad.setAttribute('x2', '0');
        linearGrad.setAttribute('y2', '1');
        
        const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop1.setAttribute('offset', '0%');
        stop1.setAttribute('stop-color', '#C5A059');
        stop1.setAttribute('stop-opacity', '0.2');
        
        const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop2.setAttribute('offset', '100%');
        stop2.setAttribute('stop-color', '#C5A059');
        stop2.setAttribute('stop-opacity', '0.0');
        
        linearGrad.appendChild(stop1);
        linearGrad.appendChild(stop2);
        defs.appendChild(linearGrad);
        svg.appendChild(defs);
    }
    
    // 6. Draw Line Path
    if (pathD) {
        const linePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        linePath.setAttribute('d', pathD);
        linePath.setAttribute('stroke', '#C5A059');
        linePath.setAttribute('stroke-width', '3');
        linePath.setAttribute('fill', 'none');
        linePath.setAttribute('stroke-linecap', 'round');
        svg.appendChild(linePath);
    }
    
    // 7. Draw Dots & Tooltips Hover triggers
    points.forEach((p, idx) => {
        // Dot group
        const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        group.setAttribute('style', 'cursor: pointer;');
        
        // Inner circle
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', p.x);
        circle.setAttribute('cy', p.y);
        circle.setAttribute('r', '5');
        circle.setAttribute('fill', '#FFFFFF');
        circle.setAttribute('stroke', '#C5A059');
        circle.setAttribute('stroke-width', '2');
        
        // Large transparent hover capture area
        const hoverArea = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        hoverArea.setAttribute('cx', p.x);
        hoverArea.setAttribute('cy', p.y);
        hoverArea.setAttribute('r', '15');
        hoverArea.setAttribute('fill', 'transparent');
        
        group.appendChild(circle);
        group.appendChild(hoverArea);
        
        // Hover listeners
        group.addEventListener('mouseenter', () => {
            circle.setAttribute('r', '7');
            circle.setAttribute('fill', '#C5A059');
            circle.setAttribute('stroke', '#FFFFFF');
            
            // Show custom tooltip above
            showChartTooltip(container, p.x, p.y - 12, `$${p.value.toFixed(2)}`);
        });
        
        group.addEventListener('mouseleave', () => {
            circle.setAttribute('r', '5');
            circle.setAttribute('fill', '#FFFFFF');
            circle.setAttribute('stroke', '#C5A059');
            removeChartTooltip(container);
        });
        
        svg.appendChild(group);
    });
    
    container.appendChild(svg);
}

function showChartTooltip(container, x, y, value) {
    removeChartTooltip(container);
    
    const tip = document.createElement('div');
    tip.id = 'chart-tip';
    tip.style.position = 'absolute';
    tip.style.left = `${x}px`;
    tip.style.top = `${y}px`;
    tip.style.transform = 'translate(-50%, -100%)';
    tip.style.background = '#3A322F';
    tip.style.color = '#FCFBF7';
    tip.style.padding = '0.4rem 0.8rem';
    tip.style.fontSize = '0.75rem';
    tip.style.fontFamily = 'Inter, sans-serif';
    tip.style.fontWeight = '500';
    tip.style.whiteSpace = 'nowrap';
    tip.style.pointerEvents = 'none';
    tip.style.boxShadow = '0 5px 15px rgba(0,0,0,0.15)';
    tip.style.zIndex = '999';
    
    // Add small triangle below
    const arrow = document.createElement('div');
    arrow.style.position = 'absolute';
    arrow.style.bottom = '-4px';
    arrow.style.left = '50%';
    arrow.style.transform = 'translateX(-50%)';
    arrow.style.borderLeft = '4px solid transparent';
    arrow.style.borderRight = '4px solid transparent';
    arrow.style.borderTop = '4px solid #3A322F';
    tip.appendChild(arrow);
    
    tip.appendChild(document.createTextNode(value));
    container.appendChild(tip);
}

function removeChartTooltip(container) {
    const existing = container.querySelector('#chart-tip');
    if (existing) existing.remove();
}
