<?php

namespace Arkitect\Expression;

interface ExpressionDescription
{
    public function toString();

    public function getPattern();
}