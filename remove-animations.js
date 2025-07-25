#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Danh sách các thuộc tính cần loại bỏ
const removeProperties = [
    'box-shadow',
    'animation',
    'transform',
    'transition',
    '-webkit-animation',
    '-moz-animation',
    '-o-animation',
    '-webkit-transform',
    '-moz-transform',
    '-o-transform',
    'filter',
    'backdrop-filter',
    'text-shadow'
];

// Loại bỏ @keyframes
function removeKeyframes(content) {
    return content.replace(/@keyframes[^{]*\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/g, '');
}

// Loại bỏ các thuộc tính animation
function removeAnimationProperties(content) {
    let result = content;
    
    removeProperties.forEach(prop => {
        // Loại bỏ thuộc tính và giá trị của nó
        const regex = new RegExp(`\\s*${prop.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')}\\s*:[^;]*;?`, 'gi');
        result = result.replace(regex, '');
    });
    
    return result;
}

// Làm sạch CSS (loại bỏ rule rỗng)
function cleanEmptyRules(content) {
    // Loại bỏ các rule CSS rỗng
    return content.replace(/[^{}]+\{\s*\}/g, '');
}

// Xử lý file CSS
function processCSSFile(filePath) {
    try {
        console.log(`Processing: ${filePath}`);
        
        let content = fs.readFileSync(filePath, 'utf8');
        
        // Loại bỏ @keyframes
        content = removeKeyframes(content);
        
        // Loại bỏ animation properties
        content = removeAnimationProperties(content);
        
        // Làm sạch rule rỗng
        content = cleanEmptyRules(content);
        
        // Làm sạch khoảng trắng thừa
        content = content.replace(/\n\s*\n/g, '\n');
        
        fs.writeFileSync(filePath, content);
        
        console.log(`✅ Completed: ${filePath}`);
        
    } catch (error) {
        console.error(`❌ Error processing ${filePath}:`, error.message);
    }
}

// Tìm tất cả file CSS
function findCSSFiles(dir) {
    const files = [];
    
    try {
        const items = fs.readdirSync(dir);
        
        items.forEach(item => {
            const fullPath = path.join(dir, item);
            const stat = fs.statSync(fullPath);
            
            if (stat.isDirectory()) {
                files.push(...findCSSFiles(fullPath));
            } else if (path.extname(item) === '.css') {
                files.push(fullPath);
            }
        });
    } catch (error) {
        console.error(`Error reading directory ${dir}:`, error.message);
    }
    
    return files;
}

// Main function
function main() {
    const assetsDir = path.join(__dirname, 'assets');
    
    if (!fs.existsSync(assetsDir)) {
        console.error('Assets directory not found!');
        return;
    }
    
    console.log('🚀 Starting animation and shadow removal...\n');
    
    const cssFiles = findCSSFiles(assetsDir);
    
    if (cssFiles.length === 0) {
        console.log('No CSS files found.');
        return;
    }
    
    console.log(`Found ${cssFiles.length} CSS files:`);
    cssFiles.forEach(file => console.log(`  - ${file}`));
    console.log('');
    
    cssFiles.forEach(processCSSFile);
    
    console.log('\n🎉 All CSS files have been processed!');
    console.log('✨ Animations and shadows have been removed.');
}

// Run the script
main();
