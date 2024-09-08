<?php

namespace byalperens\furnace\blocks;

class BetterFurnace extends BaseBetterFurnace{

    private bool $lit = false;

    /**
     * @param bool $lit
     * @return void
     */
    public function setLit(bool $lit): void{
        $this->lit = $lit;
    }

    /**
     * @return bool
     */
    public function isLit(): bool{
        return $this->lit;
    }

    /**
     * @return int
     */
    public function getLightLevel(): int{
        return 0;
    }
}
