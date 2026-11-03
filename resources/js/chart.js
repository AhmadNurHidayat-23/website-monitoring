const ns = 'http://www.w3.org/2000/svg';
const element = (name, attributes, text) => {
    const node = document.createElementNS(ns, name);
    Object.entries(attributes).forEach(([key, value]) => node.setAttribute(key, value));
    if (text !== undefined) node.textContent = text;
    return node;
};

document.querySelectorAll('[data-points]').forEach((svg) => {
    const points = JSON.parse(svg.dataset.points);
    if (!points.length) return;
    const draw = () => {
        const width = svg.getBoundingClientRect().width < 600 ? 600 : 1000;
        svg.setAttribute('viewBox', `0 0 ${width} 240`);
        svg.querySelectorAll(':scope > :not(desc)').forEach(node => node.remove());
        const left = 64, right = width - 26, top = 22, bottom = 192;
        const max = Math.max(10, Math.ceil(Math.max(...points.map(point => point.ms)) / 10) * 10);
        for (let i = 0; i <= 4; i++) {
            const y = top + (bottom - top) * i / 4;
            svg.append(element('line', {x1: left, y1: y, x2: right, y2: y, class: 'chart-grid'}));
            svg.append(element('text', {x: left - 12, y: y + 4, 'text-anchor': 'end', class: 'chart-label'}, Math.round(max * (1 - i / 4))));
        }
        const coordinates = points.map((point, index) => ({
            ...point,
            x: points.length === 1 ? (left + right) / 2 : left + (right - left) * index / (points.length - 1),
            y: bottom - point.ms / max * (bottom - top),
        }));
        // Dashed segments distinguish checks with a failure from successful responses.
        for (let i = 1; i < coordinates.length; i++) {
            const prev = coordinates[i - 1], current = coordinates[i];
            svg.append(element('line', {x1: prev.x, y1: prev.y, x2: current.x, y2: current.y,
                class: prev.status === 'up' && current.status === 'up' ? 'chart-line' : 'chart-line chart-failure'}));
        }
        coordinates.forEach(point => {
            const group = element('g', {tabindex: '0', 'aria-label': `${point.time}, ${point.ms} ms, ${point.status === 'up' ? 'Up' : 'Down'}`});
            group.append(element('title', {}, `${point.time} · ${point.ms} ms · ${point.status === 'up' ? 'Up' : 'Down'}`));
            if (point.status === 'up') group.append(element('circle', {cx: point.x, cy: point.y, r: 4, class: 'chart-dot'}));
            else group.append(element('path', {d: `M ${point.x - 4} ${point.y - 4} l 8 8 M ${point.x - 4} ${point.y + 4} l 8 -8`, class: 'chart-cross'}));
            svg.append(group);
        });
        svg.append(element('text', {x: left, y: 225, class: 'chart-label'}, points[0].time));
        if (points.length > 1) svg.append(element('text', {x: right, y: 225, 'text-anchor': 'end', class: 'chart-label'}, points.at(-1).time));
        svg.closest('.chart-frame').classList.add('chart-ready');
    };
    new ResizeObserver(draw).observe(svg);
    draw();
});
