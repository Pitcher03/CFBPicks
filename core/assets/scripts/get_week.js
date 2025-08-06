const TEAMS_BY_CONFERENCE = {
    "p12" : ["Oregon St", "Washington St"]
};

const DATES = [
{week:0,start:new Date("Jul 1, 2025"),futuresKey:"p12",futures:"Pac 2"},
{week:1,start:new Date("Aug 26, 2025"),futuresKey:"p12",futures:"Pac 2"},
{week:2,start:new Date("Sep 2, 2025"),futuresKey:"mac",futures:"MAC"},
{week:3,start:new Date("Sep 9, 2025"),futuresKey:"mtn",futures:"Mountain West"},
{week:4,start:new Date("Sep 16, 2025"),futuresKey:"b12",futures:"Big 12"},
{week:5,start:new Date("Sep 23, 2025"),futuresKey:"aac",futures:"American"},
{week:6,start:new Date("Sep 30, 2025"),futuresKey:"b10",futures:"Big 10"},
{week:7,start:new Date("Oct 7, 2025"),futuresKey:"sec",futures:"SEC"},
{week:8,start:new Date("Oct 14, 2025"),futuresKey:"usa",futures:"C-USA"},
{week:9,start:new Date("Oct 21, 2025"),futuresKey:"sun",futures:"Sun Belt"},
{week:10,start:new Date("Oct 28, 2025"),futuresKey:"acc",futures:"ACC"},
{week:11,start:new Date("Nov 4, 2025"),futuresKey:"seed_1",futures:"Final 4"},
{week:12,start:new Date("Nov 11, 2025"),futuresKey:"seed_2",futures:"Final 4"},
{week:13,start:new Date("Nov 18, 2025"),futuresKey:"seed_3",futures:"Final 4"},
{week:14,start:new Date("Nov 25, 2025"),futuresKey:"seed_4",futures:"Final 4"},
{week:15,start:new Date("Dec 2, 2025"),futuresKey:"armynavy",futures:"Army/Navy Game"},
{week:16,start:new Date("Dec 9, 2025"),futuresKey:"champion",futures:"Title"}
];

function getWeek(date = new Date()) {
    for (const row of DATES) {
        if (date < row.start) return row.week - 1;
    }
    return 16;
}

function getFutures(date = new Date()) {
    for (let i = DATES.length; i > 0; i--) {
        let row = DATES[i-1];
        if (date > row.start) return {futuresKey: row.futuresKey, futures: row.futures};
    }
}

//const debugDate = "Oct 2, 2025";

const current_week = typeof debugDate !== 'undefined' ?
    getWeek(new Date(debugDate)) :
    getWeek();
const current_futures = typeof debugDate !== 'undefined' ?
    getFutures(new Date(debugDate)) :
    getFutures();