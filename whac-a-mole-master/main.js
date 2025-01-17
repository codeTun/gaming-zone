let currMoleTile
let CurrPlantTile
let score=0
let gameOver=false

window.onload=function(){
    setGame()
}
function setGame(){
    // configuration du board de jeu
    for(let i=0;i<9;i++){
        //pointeur sur div
        let tile=document.createElement("div")
        tile.id=i.toString()
        tile.addEventListener("click",selectTile)
        document.getElementById("board").appendChild(tile)
    
    }
    setInterval(setMole,2000)//1000 milisecondes=
    setInterval(setPlant,2000) //2000 milisecondes


}
function getRandomTile(){
    let num=Math.floor(Math.random()*9)
    return num.toString()
}


function setMole(){
    if(gameOver){
        return
    }
if(currMoleTile){
    currMoleTile.innerHTML="";
    
 
}

    let mole=document.createElement("img")
    mole.src="./images/monty-mole.png"

    let num=getRandomTile()
    if(CurrPlantTile&&currMoleTile.id==num ){
        return
    }
     
    currMoleTile=document.getElementById(num)
    currMoleTile.appendChild(mole)

}


function setPlant(){

    if(gameOver){
        return
    }
    if (CurrPlantTile){
        CurrPlantTile.innerHTML=" "
    }
    let plant=document.createElement("img")
    plant.src="./images/piranha-plant.png"
    let num =getRandomTile()
    if(currMoleTile&&currMoleTile.id==num){
        return
    }
    CurrPlantTile=document.getElementById(num)
    CurrPlantTile.appendChild(plant)
}
function selectTile(){
    if(gameOver){
        return
    }
    if (this ==currMoleTile){
        score+=10
        document.getElementById("score").innerText=score.toString()
        
    }
    else if (this==CurrPlantTile){
        document.getElementById("score").innerText = 
    "GAME OVER : AND YOU HAVE " + score.toString() + " AS A SCORE";

        gameOver=true
        score=0
    }
}