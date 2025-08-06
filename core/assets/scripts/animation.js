const TEAMS = ["Air Force","Akron","Alabama","Appalachian St","Arizona","Arizona St","Arkansas","Arkansas St","Army","Auburn",
    "BYU","Ball St","Baylor","Boise St","Boston College","Bowling Green","Buffalo","California","Central Michigan","Charlotte",
    "Cincinnati","Clemson","Coastal Carolina","Colorado","Colorado St","Delaware","Duke","East Carolina","Eastern Michigan","FIU","Florida",
    "Florida Atlantic","Florida St","Fresno St","Georgia","Georgia Southern","Georgia St","Georgia Tech","Hawaii","Houston","Illinois",
    "Indiana","Iowa","Iowa St","Jacksonville St","James Madison","Kansas","Kansas St","Kennesaw St","Kent St","Kentucky",
    "LSU","Liberty","Louisiana","Louisiana Monroe","Louisiana Tech","Louisville","Marshall","Maryland","Memphis","Miami","Miami OH",
    "Michigan","Michigan St","Middle Tennessee","Minnesota","Mississippi St","Missouri","Missouri St","North Carolina St","Navy","Nebraska","Nevada",
    "New Mexico","New Mexico St","North Carolina","North Texas","Northern Illinois","Northwestern","Notre Dame","Ohio","Ohio St","Oklahoma",
    "Oklahoma St","Old Dominion","Ole Miss","Oregon","Oregon St","Penn St","Pittsburgh","Purdue","Rice","Rutgers","SMU",
    "Sam Houston","San Diego St","San Jose St","South Alabama","South Carolina","Southern Miss","Stanford","Syracuse","TCU","Temple",
    "Tennessee","Texas","Texas A&M","Texas St","Texas Tech","Toledo","Troy","Tulane","Tulsa","UAB",
    "UCF","UCLA","UConn","UMass","UNLV","USC","South Florida","UTEP",
    "UTSA","Utah","Utah St","Vanderbilt","Virginia","Virginia Tech","Wake Forest","Washington","Washington St","West Virginia",
    "Western Kentucky","Western Michigan","Wisconsin","Wyoming"];

function launchAnimation() {
    const wavesDom = document.getElementById('waves');
    if (!wavesDom) { console.error("Fatal: Waves container not found!"); return; }
    const wavesChart = echarts.init(wavesDom);
    const noise = getNoiseHelper();
    const config = {
        frequency: 750,
        offsetX: 100,
        offsetY: 100,
        minSize: 0.1,
        maxSize: 40,
        duration: 10000,
        backgroundColor: '#000',
    };
    const mode = Math.floor(Math.random()*10);
    console.log("Wave animation: mode " + mode);
    
    noise.seed(Math.random());
    const wavesOption = {
        backgroundColor: config.backgroundColor,
        graphic: {
            elements: createElements(wavesChart, noise, config, mode)
        }
    };
    wavesChart.setOption(wavesOption);
}

function createElements(chartInstance, noise, config, mode) {
    const elements = [];
    const chartWidth = chartInstance.getWidth();
    const chartHeight = chartInstance.getHeight();
    const available_teams = [...TEAMS].sort(() => Math.random() - 0.5);
    let teamIndex = 0;

    for (let x = config.maxSize; x < chartWidth; x += config.maxSize * 2) {
        for (let y = config.maxSize; y < chartHeight; y += config.maxSize * 2) {
            const rand = noise.perlin2(
                x / config.frequency + config.offsetX,
                y / config.frequency + config.offsetY
            );
            
            const d = {
                x: Math.abs(0.5 * chartWidth / (config.maxSize * 2) - x),
                y: Math.abs(0.5 * chartHeight / (config.maxSize * 2) - y)
            };
            
            let duration;
            let delay;

            switch(mode) {
                default:
                    duration = config.duration;
                    delay = (rand - 1) * config.duration;
                    break;
                case 1:
                    duration = config.duration;
                    delay = 0.5*d.x + 0.5*(chartWidth/(config.maxSize*2))*d.y;
                    break;
                case 2:
                    duration = config.duration;
                    delay = (rand - 1) * config.duration + (15 * d.x + 15 * d.y);
                    break;
            }
            
            if (teamIndex >= available_teams.length) {
                teamIndex = 0;
            }
            const imagePath = "assets/logos/" +available_teams[teamIndex] + ".png";
            teamIndex++;

            elements.push({
                type: 'image',
                x: x - config.maxSize,
                y: y - config.maxSize,
                originX: config.maxSize,
                originY: config.maxSize,
                style: {
                    image: imagePath,
                    width: config.maxSize * 1.8,
                    height: config.maxSize * 1.8,
                    opacity: 0.9
                },
                keyframeAnimation: {
                    duration: duration,
                    loop: true,
                    delay: delay,
                    keyframes: [
                        {
                            percent: 0.5,
                            easing: 'sinusoidalInOut',
                            style: {
                                opacity: 0.1
                            },
                            scaleX: config.minSize / config.maxSize,
                            scaleY: config.minSize / config.maxSize
                        },
                        {
                            percent: 1,
                            easing: 'sinusoidalInOut',
                            style: {
                                opacity: 0.9
                            },
                            scaleX: 1,
                            scaleY: 1
                        }
                    ]
                },
            });
        }
    }
    return elements;
}

function getNoiseHelper() {
    class Grad {
        constructor(x, y, z) { this.x = x; this.y = y; this.z = z; }
        dot2(x, y) { return this.x * x + this.y * y; }
        dot3(x, y, z) { return this.x * x + this.y * y + this.z * z; }
    }
    const grad3 = [
        new Grad(1, 1, 0), new Grad(-1, 1, 0), new Grad(1, -1, 0), new Grad(-1, -1, 0),
        new Grad(1, 0, 1), new Grad(-1, 0, 1), new Grad(1, 0, -1), new Grad(-1, 0, -1),
        new Grad(0, 1, 1), new Grad(0, -1, 1), new Grad(0, 1, -1), new Grad(0, -1, -1)
    ];
    const p = [
        151, 160, 137, 91, 90, 15, 131, 13, 201, 95, 96, 53, 194, 233, 7, 225, 140,
        36, 103, 30, 69, 142, 8, 99, 37, 240, 21, 10, 23, 190, 6, 148, 247, 120,
        234, 75, 0, 26, 197, 62, 94, 252, 219, 203, 117, 35, 11, 32, 57, 177, 33,
        88, 237, 149, 56, 87, 174, 20, 125, 136, 171, 168, 68, 175, 74, 165, 71,
        134, 139, 48, 27, 166, 77, 146, 158, 231, 83, 111, 229, 122, 60, 211, 133,
        230, 220, 105, 92, 41, 55, 46, 245, 40, 244, 102, 143, 54, 65, 25, 63, 161,
        1, 216, 80, 73, 209, 76, 132, 187, 208, 89, 18, 169, 200, 196, 135, 130,
        116, 188, 159, 86, 164, 100, 109, 198, 173, 186, 3, 64, 52, 217, 226, 250,
        124, 123, 5, 202, 38, 147, 118, 126, 255, 82, 85, 212, 207, 206, 59, 227,
        47, 16, 58, 17, 182, 189, 28, 42, 223, 183, 170, 213, 119, 248, 152, 2, 44,
        154, 163, 70, 221, 153, 101, 155, 167, 43, 172, 9, 129, 22, 39, 253, 19, 98,
        108, 110, 79, 113, 224, 232, 178, 185, 112, 104, 218, 246, 97, 228, 251, 34,
        242, 193, 238, 210, 144, 12, 191, 179, 162, 241, 81, 51, 145, 235, 249, 14,
        239, 107, 49, 192, 214, 31, 181, 199, 106, 157, 184, 84, 204, 176, 115, 121,
        50, 45, 127, 4, 150, 254, 138, 236, 205, 93, 222, 114, 67, 29, 24, 72, 243,
        141, 128, 195, 78, 66, 215, 61, 156, 180
    ];
    let perm = new Array(512);
    let gradP = new Array(512);
    function seed(seed) {
        if (seed > 0 && seed < 1) { seed *= 65536; }
        seed = Math.floor(seed);
        if (seed < 256) { seed |= seed << 8; }
        for (let i = 0; i < 256; i++) {
            let v = (i & 1) ? p[i] ^ (seed & 255) : p[i] ^ ((seed >> 8) & 255);
            perm[i] = perm[i + 256] = v;
            gradP[i] = gradP[i + 256] = grad3[v % 12];
        }
    }
    seed(0);
    const fade = (t) => t * t * t * (t * (t * 6 - 15) + 10);
    const lerp = (a, b, t) => (1 - t) * a + t * b;
    function perlin2(x, y) {
        let X = Math.floor(x), Y = Math.floor(y);
        x = x - X; y = y - Y;
        X = X & 255; Y = Y & 255;
        const n00 = gradP[X + perm[Y]].dot2(x, y);
        const n01 = gradP[X + perm[Y + 1]].dot2(x, y - 1);
        const n10 = gradP[X + 1 + perm[Y]].dot2(x - 1, y);
        const n11 = gradP[X + 1 + perm[Y + 1]].dot2(x - 1, y - 1);
        const u = fade(x);
        return lerp(lerp(n00, n10, u), lerp(n01, n11, u), fade(y));
    }
    return { seed, perlin2 };
}